import { SYSTEM_PROMPT, localReply } from '@/lib/immo-agent';
import { dictionaries } from '@/lib/dictionaries';
import { getLocale } from '@/lib/locale';

export const runtime = 'nodejs';

type Msg = { role: 'user' | 'assistant'; content: string };

function parseMessages(body: unknown): Msg[] | null {
  if (typeof body !== 'object' || body === null || !('messages' in body)) return null;
  const raw = (body as { messages: unknown }).messages;
  if (!Array.isArray(raw)) return null;
  const out: Msg[] = [];
  for (const m of raw.slice(-10)) {
    if (typeof m !== 'object' || m === null) continue;
    const { role, content } = m as { role?: unknown; content?: unknown };
    if ((role === 'user' || role === 'assistant') && typeof content === 'string' && content.trim()) {
      out.push({ role, content: content.slice(0, 600) });
    }
  }
  while (out.length > 0 && out[0].role !== 'user') out.shift();
  return out.length > 0 && out[out.length - 1].role === 'user' ? out : null;
}

// Limite simple par IP (10 min) pour protéger la clé API : 20 questions maximum.
const hits = new Map<string, { n: number; reset: number }>();
function limited(ip: string) {
  const now = Date.now();
  if (hits.size > 500) hits.clear();
  const e = hits.get(ip);
  if (!e || e.reset < now) { hits.set(ip, { n: 1, reset: now + 600_000 }); return false; }
  e.n += 1;
  return e.n > 20;
}

const textResponse = (text: string, status = 200) =>
  new Response(text, { status, headers: { 'Content-Type': 'text/plain; charset=utf-8', 'Cache-Control': 'no-store' } });

export async function POST(req: Request) {
  const locale = getLocale();
  const t = dictionaries[locale];
  const ip = req.headers.get('x-forwarded-for')?.split(',')[0]?.trim() ?? 'local';
  if (limited(ip)) return textResponse(t.api.rateLimited, 429);
  const messages = parseMessages(await req.json().catch(() => null));
  if (!messages) return textResponse(t.api.chatInvalid, 400);

  const apiKey = process.env.ANTHROPIC_API_KEY;
  const fallback = () => textResponse(localReply(messages[messages.length - 1].content, locale));
  if (!apiKey) return fallback();

  const upstream = await fetch('https://api.anthropic.com/v1/messages', {
    method: 'POST',
    headers: { 'content-type': 'application/json', 'x-api-key': apiKey, 'anthropic-version': '2023-06-01' },
    body: JSON.stringify({
      model: process.env.IMMO_AGENT_MODEL ?? 'claude-sonnet-5-5',
      max_tokens: 700,
      system: `${SYSTEM_PROMPT}\n\nLangue du navigateur de la personne : ${locale === 'fr' ? 'français' : 'anglais'}.`,
      messages,
      stream: true,
    }),
  }).catch(() => null);
  if (!upstream || !upstream.ok || !upstream.body) return fallback();

  const encoder = new TextEncoder();
  const decoder = new TextDecoder();
  const source = upstream.body.getReader();
  const stream = new ReadableStream<Uint8Array>({
    async start(controller) {
      let buffer = '';
      try {
        for (;;) {
          const { done, value } = await source.read();
          if (done) break;
          buffer += decoder.decode(value, { stream: true });
          const lines = buffer.split('\n');
          buffer = lines.pop() ?? '';
          for (const line of lines) {
            if (!line.startsWith('data: ')) continue;
            try {
              const ev = JSON.parse(line.slice(6)) as { type?: string; delta?: { text?: string } };
              if (ev.type === 'content_block_delta' && ev.delta?.text) controller.enqueue(encoder.encode(ev.delta.text));
            } catch {
              /* ligne SSE partielle ignorée */
            }
          }
        }
      } finally {
        controller.close();
      }
    },
  });
  return new Response(stream, { headers: { 'Content-Type': 'text/plain; charset=utf-8', 'Cache-Control': 'no-store' } });
}
