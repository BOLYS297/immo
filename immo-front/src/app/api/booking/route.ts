import { NextResponse } from 'next/server';
import { DEADLINE_KEYS, NEED_KEYS, generateSlots } from '@/lib/booking';
import { dictionaries } from '@/lib/dictionaries';
import { getLocale } from '@/lib/locale';
import { clientIp, cmsForm } from '@/server/cms-api';

const clean = (v: unknown, max: number) => (typeof v === 'string' ? v.trim().slice(0, max) : '');

/** Créneaux libres : ceux déjà réservés (lus dans Laravel) sont retirés. */
export async function GET(req: Request) {
  const { status, data } = await cmsForm<{ slots?: unknown }>('GET', '/bookings/taken', { ip: clientIp(req) });
  if (status !== 200 || !Array.isArray(data?.slots)) return NextResponse.json({ error: 'unavailable' }, { status: 503 });
  const taken = new Set(data.slots);
  return NextResponse.json({ slots: generateSlots().filter((s) => !taken.has(s)) }, { headers: { 'Cache-Control': 'no-store' } });
}

/** Réservation : contrôlée ici (créneau proposé), enregistrée et notifiée par Laravel (admin → Rendez-vous). */
export async function POST(req: Request) {
  const locale = getLocale();
  const api = dictionaries[locale].pages.contact.api;
  const body: unknown = await req.json().catch(() => null);
  if (typeof body !== 'object' || body === null) return NextResponse.json({ error: api.invalid }, { status: 400 });
  const b = body as Record<string, unknown>;
  if (clean(b.website, 50)) return NextResponse.json({ ok: true });

  const booking = { need: clean(b.need, 10), deadline: clean(b.deadline, 10), slot: clean(b.slot, 30), name: clean(b.name, 80), email: clean(b.email, 120), company: clean(b.company, 100) };
  const valid =
    (NEED_KEYS as readonly string[]).includes(booking.need) && (DEADLINE_KEYS as readonly string[]).includes(booking.deadline) &&
    generateSlots().includes(booking.slot) && booking.name && booking.company && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(booking.email);
  if (!valid) return NextResponse.json({ error: api.invalid }, { status: 400 });

  const { status } = await cmsForm('POST', '/bookings', { body: { ...booking, locale }, ip: clientIp(req) });
  if (status === 201) return NextResponse.json({ ok: true });
  if (status === 409) return NextResponse.json({ error: api.taken }, { status: 409 });
  if (status === 422) return NextResponse.json({ error: api.invalid }, { status: 400 });
  return NextResponse.json({ error: api.failed }, { status: status === 429 ? 429 : 502 });
}
