import { NextResponse } from 'next/server';
import { getClientSession } from '@/server/auth';
import { cmsForm } from '@/server/cms-api';

/** Acceptation en ligne d'un document « à signer » : contrôlée et enregistrée par Laravel. */
export async function POST(req: Request) {
  const s = await getClientSession();
  if (!s || s.preview) return NextResponse.json({ error: 'auth' }, { status: 401 });
  const b = (await req.json().catch(() => null)) as { id?: unknown } | null;
  if (typeof b?.id !== 'string' || !/^\d{1,20}$/.test(b.id)) return NextResponse.json({ error: 'invalid' }, { status: 400 });
  const { status } = await cmsForm('POST', `/portal/documents/${b.id}/sign`, { token: s.token });
  if (status === 200) return NextResponse.json({ ok: true });
  return NextResponse.json({ error: status === 401 || status === 403 ? 'auth' : 'invalid' }, { status: status === 401 || status === 403 ? 401 : 400 });
}
