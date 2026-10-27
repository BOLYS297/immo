import { NextResponse } from 'next/server';
import { endSession, getSession } from '@/server/auth';
import { cmsForm } from '@/server/cms-api';

/** Déconnexion : le jeton Laravel du client est révoqué, puis le cookie supprimé. */
export async function POST() {
  const s = await getSession();
  if (s?.token) await cmsForm('POST', '/portal/logout', { token: s.token });
  endSession();
  return NextResponse.json({ ok: true });
}

/** Session expirée ou révoquée côté Laravel : supprime le cookie puis renvoie vers la page de connexion. */
export function GET(req: Request) {
  endSession();
  return NextResponse.redirect(new URL('/connexion', req.url));
}
