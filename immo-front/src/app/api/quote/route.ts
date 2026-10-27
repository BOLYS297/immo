import { NextResponse } from 'next/server';
import { dictionaries } from '@/lib/dictionaries';
import { getLocale } from '@/lib/locale';
import { clientIp, cmsForm } from '@/server/cms-api';

const DOMAINS = ['ia', 'web', 'infra'];
const clean = (v: unknown, max: number) => (typeof v === 'string' ? v.trim().slice(0, max) : '');

/** Demande de chiffrage : contrôlée ici, enregistrée et notifiée par Laravel (admin → Demandes de devis). */
export async function POST(req: Request) {
  const locale = getLocale();
  const api = dictionaries[locale].pages.services.api;
  const body: unknown = await req.json().catch(() => null);
  if (typeof body !== 'object' || body === null) return NextResponse.json({ error: api.invalid }, { status: 400 });
  const b = body as Record<string, unknown>;
  if (clean(b.website, 50)) return NextResponse.json({ ok: true }); // robot : réponse neutre

  const lead = { name: clean(b.name, 80), email: clean(b.email, 120), phone: clean(b.phone, 30), company: clean(b.company, 100), domain: clean(b.domain, 10), details: clean(b.details, 1500) };
  if (!lead.name || !lead.company || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(lead.email) || !/^\+?[0-9 ().-]{6,30}$/.test(lead.phone) || !DOMAINS.includes(lead.domain) || lead.details.length < 20) {
    return NextResponse.json({ error: api.invalid }, { status: 400 });
  }

  const { status } = await cmsForm('POST', '/quotes', { body: { ...lead, locale }, ip: clientIp(req) });
  if (status === 201) return NextResponse.json({ ok: true });
  if (status === 422) return NextResponse.json({ error: api.invalid }, { status: 400 });
  if (status === 429) return NextResponse.json({ error: api.failed }, { status: 429 });
  return NextResponse.json({ error: api.failed }, { status: 502 });
}
