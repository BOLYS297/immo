import { NextResponse } from 'next/server';
import type { DiagnosticReport } from '@/lib/diagnostic';
import { buildDemoReport } from '@/lib/diagnostic';
import { dictionaries } from '@/lib/dictionaries';
import { getLocale } from '@/lib/locale';

export async function POST(req: Request) {
  const locale = getLocale();
  const body: unknown = await req.json().catch(() => null);
  const raw = typeof body === 'object' && body !== null && 'query' in body ? String((body as { query: unknown }).query) : '';
  const query = raw.trim().slice(0, 120);
  if (query.length < 2) return NextResponse.json({ error: dictionaries[locale].api.diagnosticInvalid }, { status: 400 });

  // Branchement n8n : si le webhook est défini, il produit le bilan ; sinon bilan de démonstration.
  const webhook = process.env.N8N_DIAGNOSTIC_WEBHOOK_URL;
  if (webhook) {
    try {
      const res = await fetch(webhook, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query, locale }),
        signal: AbortSignal.timeout(15000),
      });
      if (res.ok) return NextResponse.json((await res.json()) as DiagnosticReport);
    } catch {
      /* repli sur le bilan de démonstration */
    }
  }
  return NextResponse.json(buildDemoReport(query, locale));
}
