import { NextResponse } from 'next/server';
import type { PortalUser } from '@/lib/portal-types';
import { startSession } from '@/server/auth';
import { cmsForm } from '@/server/cms-api';

/**
 * Aperçu du portail ouvert depuis l'admin Laravel (Clients → Aperçu du portail).
 * Le code à usage unique (2 min) est échangé contre un jeton en lecture seule ; la session dure 30 min.
 */
export async function GET(req: Request) {
  const code = new URL(req.url).searchParams.get('code') ?? '';
  const { status, data } = await cmsForm<{ token?: string; user?: PortalUser }>('POST', '/portal/preview', { body: { code } });
  if (status !== 200 || !data?.token || !data.user) return NextResponse.redirect(new URL('/connexion', req.url));
  await startSession({ id: data.user.id, role: 'client', email: data.user.email, token: data.token, preview: true }, 1800);
  return NextResponse.redirect(new URL('/dashboard', req.url));
}
