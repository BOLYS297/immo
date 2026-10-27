import { NextResponse } from 'next/server';
import { getClientSession } from '@/server/auth';
import { cmsForm } from '@/server/cms-api';

/** Changement de mot de passe du client : vérifié par Laravel, qui ferme ses autres connexions. */
export async function POST(req: Request) {
  const s = await getClientSession();
  if (!s || s.preview) return NextResponse.json({ error: 'auth' }, { status: 401 });
  const b = (await req.json().catch(() => null)) as { current?: unknown; next?: unknown } | null;
  if (typeof b?.current !== 'string' || typeof b?.next !== 'string' || b.next.length < 10 || b.next.length > 100) return NextResponse.json({ error: 'invalid' }, { status: 400 });
  const { status } = await cmsForm('PUT', '/portal/password', { body: { current: b.current, next: b.next }, token: s.token });
  if (status === 200) return NextResponse.json({ ok: true });
  if (status === 403) return NextResponse.json({ error: 'wrong' }, { status: 403 });
  return NextResponse.json({ error: status === 401 ? 'auth' : 'invalid' }, { status: status === 401 ? 401 : 400 });
}
