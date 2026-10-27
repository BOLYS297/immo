import { NextResponse } from 'next/server';
import { getClientSession } from '@/server/auth';
import { cmsForm } from '@/server/cms-api';

const str = (v: unknown, max: number) => (typeof v === 'string' ? v.trim().slice(0, max) : '');

/** Nouveau ticket de support : enregistré et notifié par Laravel (10 tickets / 10 min par client). */
export async function POST(req: Request) {
  const s = await getClientSession();
  if (!s || s.preview) return NextResponse.json({ error: 'auth' }, { status: 401 });
  const b = (await req.json().catch(() => null)) as Record<string, unknown> | null;
  const ticket = { title: str(b?.title, 120), message: str(b?.message, 2000), priority: Math.min(2, Math.max(0, Math.round(Number(b?.priority) || 0))) };
  if (!ticket.title) return NextResponse.json({ error: 'invalid' }, { status: 400 });
  const { status, data } = await cmsForm<{ id?: string }>('POST', '/portal/tickets', { body: ticket, token: s.token });
  if (status === 201) return NextResponse.json({ ok: true, id: data?.id });
  if (status === 429) return NextResponse.json({ error: 'rate' }, { status: 429 });
  return NextResponse.json({ error: status === 401 || status === 403 ? 'auth' : 'invalid' }, { status: status === 401 || status === 403 ? 401 : 400 });
}
