import { NextResponse } from 'next/server';
import { dictionaries } from '@/lib/dictionaries';
import { getLocale } from '@/lib/locale';
import { clientIp, cmsForm } from '@/server/cms-api';

const clean = (v: unknown, max: number) => (typeof v === 'string' ? v.trim().slice(0, max) : '');

/** Message « Écrivez-nous » : contrôlé ici, enregistré et notifié par Laravel (admin → Messages). */
export async function POST(req: Request) {
  const m = dictionaries[getLocale()].pages.contact.message;
  const body: unknown = await req.json().catch(() => null);
  if (typeof body !== 'object' || body === null) return NextResponse.json({ error: m.invalid }, { status: 400 });
  const b = body as Record<string, unknown>;
  if (clean(b.website, 50)) return NextResponse.json({ ok: true }); // robot : réponse neutre

  const msg = { name: clean(b.name, 100), email: clean(b.email, 255), phone: clean(b.phone, 30), subject: clean(b.subject, 200), message: clean(b.message, 4000) };
  if (!msg.name || !msg.subject || msg.message.length < 10 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(msg.email) || (msg.phone && !/^\+?[0-9 ().-]{6,30}$/.test(msg.phone))) {
    return NextResponse.json({ error: m.invalid }, { status: 400 });
  }

  const { status } = await cmsForm('POST', '/messages', { body: { ...msg, phone: msg.phone || null }, ip: clientIp(req) });
  if (status === 201) return NextResponse.json({ ok: true });
  if (status === 422) return NextResponse.json({ error: m.invalid }, { status: 400 });
  if (status === 429) return NextResponse.json({ error: m.failed }, { status: 429 });
  return NextResponse.json({ error: m.failed }, { status: 502 });
}
