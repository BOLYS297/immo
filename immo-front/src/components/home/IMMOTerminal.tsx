'use client';
import { useEffect, useRef, useState } from 'react';
import Image from 'next/image';
import { Loader2, SendHorizontal } from 'lucide-react';
import { useI18n } from '@/components/I18nProvider';
import type { BlockContent } from '@/lib/blocks';

type Msg = { role: 'user' | 'assistant'; content: string };

export default function IMMOTerminal({ content }: { content?: BlockContent<'terminal'> }) {
  const { t } = useI18n();
  const tt = { ...t.terminal, ...content };
  const [messages, setMessages] = useState<Msg[]>([{ role: 'assistant', content: tt.greeting }]);
  const [input, setInput] = useState('');
  const [busy, setBusy] = useState(false);
  const scroller = useRef<HTMLDivElement>(null);

  // La salutation suit la langue tant que la conversation n'a pas commencé
  useEffect(() => { setMessages((m) => (m.length === 1 ? [{ role: 'assistant', content: tt.greeting }] : m)); }, [tt.greeting]);
  useEffect(() => { const el = scroller.current; if (el) el.scrollTop = el.scrollHeight; }, [messages]);

  const sendRef = useRef(send);
  sendRef.current = send;
  useEffect(() => {
    const onAsk = (e: Event) => { const q = (e as CustomEvent<unknown>).detail; if (typeof q === 'string') void sendRef.current(q); };
    window.addEventListener('immo:ask', onAsk);
    return () => window.removeEventListener('immo:ask', onAsk);
  }, []);

  async function send(text: string) {
    const question = text.trim();
    if (!question || busy) return;
    const history: Msg[] = [...messages, { role: 'user', content: question }];
    setMessages([...history, { role: 'assistant', content: '' }]);
    setInput('');
    setBusy(true);
    try {
      const res = await fetch('/api/chat', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ messages: history }) });
      if (!res.body) throw new Error('chat');
      const reader = res.body.getReader();
      const decoder = new TextDecoder();
      let answer = '';
      for (;;) {
        const { done, value } = await reader.read();
        if (done) break;
        answer += decoder.decode(value, { stream: true });
        setMessages([...history, { role: 'assistant', content: answer }]);
      }
      if (!res.ok && !answer) throw new Error('chat');
    } catch {
      setMessages([...history, { role: 'assistant', content: tt.connError }]);
    } finally {
      setBusy(false);
    }
  }

  return (
    <section id="terminal" aria-labelledby="terminal-title" className="px-6 py-28">
      <div className="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
        <div className="lg:sticky lg:top-28">
          <h2 id="terminal-title" className="max-w-md text-4xl font-semibold tracking-tighter text-immo-dark sm:text-5xl dark:text-white">{tt.title}</h2>
          <p className="mt-5 max-w-md text-lg text-immo-dark/85 dark:text-immo-satin/90">{tt.text}</p>
        </div>

        <div className="overflow-hidden rounded-2xl border border-immo-dark/20 bg-immo-dark text-immo-satin shadow-xl dark:border-white/15 dark:bg-black/25">
          <div className="flex items-center gap-3 border-b border-white/15 px-5 py-3">
            <span className="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full bg-immo-satin">
              <Image src="/images/symbol-mark.png" alt="" width={40} height={40} className="h-full w-full object-contain p-1" />
            </span>
            <div className="leading-tight"><p className="font-semibold">IMMO</p><p className="text-sm text-immo-sky">{tt.role}</p></div>
          </div>
          <div ref={scroller} role="log" aria-live="polite" aria-label={tt.logLabel} className="h-80 space-y-4 overflow-y-auto px-5 py-5">
            {messages.map((m, i) => (
              <div key={i} className={m.role === 'user' ? 'flex justify-end' : 'flex'}>
                <p className={m.role === 'user' ? 'max-w-[85%] whitespace-pre-wrap rounded-2xl rounded-br-sm bg-immo-cyan px-4 py-2.5 text-immo-dark' : 'max-w-[90%] whitespace-pre-wrap rounded-2xl rounded-bl-sm bg-white/10 px-4 py-2.5'}>
                  {m.content || <Loader2 size={18} className="animate-spin" aria-label={tt.thinking} />}
                </p>
              </div>
            ))}
          </div>
          <div className="flex flex-wrap gap-2 border-t border-white/15 px-5 py-3">
            {t.agent.quick.map((q) => (
              <button key={q} type="button" disabled={busy} onClick={() => send(q)} className="rounded-full border border-immo-sky/60 px-3.5 py-1.5 text-sm transition-colors hover:bg-immo-sky hover:text-immo-dark disabled:opacity-50">{q}</button>
            ))}
          </div>
          <form onSubmit={(e) => { e.preventDefault(); void send(input); }} className="flex items-center gap-2 border-t border-white/15 p-3">
            <label htmlFor="immo-input" className="sr-only">{tt.label}</label>
            <input id="immo-input" value={input} onChange={(e) => setInput(e.target.value)} maxLength={600} placeholder={tt.placeholder} className="min-w-0 flex-1 rounded-full bg-white/10 px-5 py-3 text-white placeholder:text-white/70" />
            <button type="submit" disabled={busy || !input.trim()} aria-label={tt.send} className="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-immo-cyan text-immo-dark transition-colors hover:bg-immo-sky disabled:opacity-50"><SendHorizontal size={18} /></button>
          </form>
        </div>
      </div>
    </section>
  );
}
