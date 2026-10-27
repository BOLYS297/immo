import { NextResponse } from 'next/server';
import { startSession } from '@/server/auth';
import { clientIp, cmsForm } from '@/server/cms-api';
import { dictionaries } from '@/lib/dictionaries';
import { getLocale } from '@/lib/locale';
import type { PortalUser } from '@/lib/portal-types';

const hits = new Map<string, { n: number; reset: number }>();

/** Connexion d'un client au portail : identifiants vérifiés par Laravel, jeton gardé dans le cookie de session. */
export async function POST(req: Request) {
  const p = dictionaries[getLocale()].pages.portalLogin;
  const ip = clientIp(req) || 'local';
  const now = Date.now(), e = hits.get(ip);
  if (!e || e.reset < now) hits.set(ip, { n: 1, reset: now + 600_000 });
  else if (++e.n > 10) return NextResponse.json({ error: p.tooMany }, { status: 429 });

  const body = (await req.json().catch(() => null)) as { email?: unknown; password?: unknown } | null;
  if (typeof body?.email !== 'string' || typeof body?.password !== 'string') return NextResponse.json({ error: p.invalid }, { status: 401 });

  const { status, data } = await cmsForm<{ token?: string; user?: PortalUser }>('POST', '/portal/login', { body: { email: body.email, password: body.password }, ip });
  if (status === 200 && data?.token && data.user) {
    await startSession({ id: data.user.id, role: 'client', email: data.user.email, token: data.token });
    return NextResponse.json({ ok: true });
  }
  if (status === 429) return NextResponse.json({ error: p.tooMany }, { status: 429 });
  if (status === 0 || status >= 500) return NextResponse.json({ error: p.failed }, { status: 503 });
  return NextResponse.json({ error: p.invalid }, { status: 401 });
}
