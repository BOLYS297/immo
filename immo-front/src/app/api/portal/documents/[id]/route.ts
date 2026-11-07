import { NextResponse } from 'next/server';
import { getClientSession } from '@/server/auth';
import { cmsFile } from '@/server/cms-api';

/**
 * Fichier joint d'un document du portail (devis, contrat, facture). Le navigateur n'a jamais le jeton :
 * le serveur Next le lit dans la session (aperçu administrateur compris) et relaie le fichier de Laravel,
 * qui vérifie que le document appartient bien au client.
 */
export async function GET(_req: Request, { params }: { params: { id: string } }) {
  const s = await getClientSession();
  if (!s) return NextResponse.json({ error: 'auth' }, { status: 401 });
  if (!/^\d{1,20}$/.test(params.id)) return NextResponse.json({ error: 'not_found' }, { status: 404 });

  const res = await cmsFile(`/portal/documents/${params.id}/file`, s.token);
  if (!res) return NextResponse.json({ error: 'unavailable' }, { status: 502 });
  if (!res.ok || !res.body) {
    const auth = res.status === 401 || res.status === 403;
    return NextResponse.json({ error: auth ? 'auth' : 'not_found' }, { status: auth ? 401 : 404 });
  }

  const headers = new Headers({ 'Cache-Control': 'private, no-store', 'X-Content-Type-Options': 'nosniff' });
  for (const name of ['Content-Type', 'Content-Length', 'Content-Disposition']) {
    const value = res.headers.get(name);
    if (value) headers.set(name, value);
  }
  return new NextResponse(res.body, { status: 200, headers });
}
