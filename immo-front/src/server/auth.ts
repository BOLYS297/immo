import { randomBytes } from 'node:crypto';
import { readFileSync, mkdirSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { cookies } from 'next/headers';
import { SignJWT, jwtVerify } from 'jose';

export const SESSION_COOKIE = 'immo-session';

function loadSecret(): Uint8Array {
  if (process.env.SESSION_SECRET) return new TextEncoder().encode(process.env.SESSION_SECRET);
  const file = path.join(process.cwd(), '.data', 'session.key');
  try { return new TextEncoder().encode(readFileSync(file, 'utf8')); } catch { /* à créer */ }
  const key = randomBytes(32).toString('hex');
  try { mkdirSync(path.dirname(file), { recursive: true }); writeFileSync(file, key); } catch { console.warn('[auth] SESSION_SECRET absent : les sessions seront perdues au redémarrage.'); }
  return new TextEncoder().encode(key);
}
const secret = loadSecret();

/**
 * Session du navigateur (cookie httpOnly signé) d'un client du portail. Les comptes vivent dans Laravel.
 * `token` = jeton Sanctum Laravel, utilisé uniquement par ce serveur. Même lu dans le cookie,
 * il ne suffit pas à appeler Laravel, qui exige aussi la clé du front (CMS_API_KEY).
 * `preview` = aperçu ouvert depuis l'admin Laravel, en lecture seule.
 */
export type Session = { id: string; role: string; email: string; token?: string; preview?: boolean };

export async function startSession(s: Session, maxAge = 604800) {
  const claims = { role: s.role, email: s.email, ...(s.token && { cms: s.token }), ...(s.preview && { preview: true }) };
  const token = await new SignJWT(claims).setProtectedHeader({ alg: 'HS256' }).setSubject(s.id).setIssuedAt().setExpirationTime(`${maxAge}s`).sign(secret);
  cookies().set(SESSION_COOKIE, token, { httpOnly: true, sameSite: 'lax', secure: process.env.NODE_ENV === 'production' && process.env.SECURE_COOKIES !== 'false', path: '/', maxAge });
}
export const endSession = () => cookies().delete(SESSION_COOKIE);

export async function getSession(): Promise<Session | null> {
  const token = cookies().get(SESSION_COOKIE)?.value;
  if (!token) return null;
  try {
    const { payload } = await jwtVerify(token, secret);
    return { id: String(payload.sub), role: String(payload.role), email: String(payload.email), ...(typeof payload.cms === 'string' && { token: payload.cms }), ...(payload.preview === true && { preview: true }) };
  } catch { return null; }
}

/** Client du portail connecté, avec son jeton Laravel. */
export async function getClientSession(): Promise<(Session & { token: string }) | null> {
  const s = await getSession();
  return s?.role === 'client' && s.token ? (s as Session & { token: string }) : null;
}
