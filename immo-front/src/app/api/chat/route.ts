import { dictionaries } from '@/lib/dictionaries';
import { getLocale } from '@/lib/locale';
import { SITE_URL } from '@/lib/seo';
import { clientIp } from '@/server/cms-api';

/**
 * Fenêtre « Agent IMMO » du site : relais vers l'agent IMMO (conteneur immo-agent sur le réseau immo_edge),
 * qui garde l'historique de chaque conversation, détient la clé Claude et applique ses propres limites.
 * La route n'envoie que le dernier message du visiteur et l'identifiant de conversation ; la réponse (texte brut
 * en streaming, ou JSON {"detail"} en cas d'erreur) est transmise telle quelle. Le secret ne quitte jamais le serveur.
 */
export const runtime = 'nodejs';
export const dynamic = 'force-dynamic';

type Msg = { role?: unknown; content?: unknown };

const json = (detail: string, status: number) =>
  Response.json({ detail }, { status, headers: { 'Cache-Control': 'no-store' } });

export async function POST(req: Request) {
  const t = dictionaries[getLocale()];
  const url = process.env.IMMO_AGENT_URL?.replace(/\/+$/, '');
  const secret = process.env.IMMO_PROXY_SECRET;
  if (!url || !secret) {
    console.error('[chat] IMMO_AGENT_URL ou IMMO_PROXY_SECRET manquant : agent IMMO injoignable.');
    return json(t.terminal.connError, 503);
  }

  const body = (await req.json().catch(() => ({}))) as { message?: unknown; messages?: unknown; conversation_id?: unknown };
  // Format du widget ({ message, conversation_id }) ou ancien format ({ messages: [...] }) : dernier message du visiteur.
  const last = Array.isArray(body.messages) ? [...(body.messages as Msg[])].reverse().find((m) => m?.role === 'user') : undefined;
  const raw = typeof body.message === 'string' ? body.message : typeof last?.content === 'string' ? last.content : '';
  const message = raw.trim();
  const conversationId = typeof body.conversation_id === 'string' && /^[\w-]{1,100}$/.test(body.conversation_id) ? body.conversation_id : null;
  if (!message) return json(t.api.chatInvalid, 422);

  let upstream: Response;
  try {
    upstream = await fetch(`${url}/chat`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Immo-Key': secret, // seul le site peut appeler l'agent
        'X-Forwarded-For': clientIp(req), // IP réelle (X-Real-IP posé par nginx) : limite anti-abus par visiteur
        'X-Immo-Site': SITE_URL,
      },
      body: JSON.stringify({ message, conversation_id: conversationId }),
      cache: 'no-store',
      signal: req.signal, // visiteur parti : l'agent arrête de rédiger
    });
  } catch (e) {
    console.error('[chat] agent IMMO injoignable :', e instanceof Error ? e.message : e);
    return json(t.terminal.connError, 502);
  }
  // Secret refusé = erreur de configuration du serveur : journalisée, le visiteur voit le message générique.
  if (upstream.status === 401) {
    console.error('[chat] agent IMMO : secret refusé (IMMO_PROXY_SECRET différent de celui de /opt/immo-agent/agent.env).');
    return json(t.terminal.connError, 502);
  }

  return new Response(upstream.body, {
    status: upstream.status,
    headers: {
      'Content-Type': upstream.headers.get('content-type') ?? 'text/plain; charset=utf-8',
      'X-Conversation-Id': upstream.headers.get('x-conversation-id') ?? '',
      'Cache-Control': 'no-store',
      'X-Accel-Buffering': 'no', // nginx : la réponse s'affiche mot par mot
    },
  });
}
