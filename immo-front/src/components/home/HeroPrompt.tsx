'use client';
import { useState } from 'react';
import { ArrowUp } from 'lucide-react';
import { useI18n } from '@/components/I18nProvider';

/** Barre de question : envoie la question à l'agent IMMO plus bas dans la page. */
export default function HeroPrompt() {
  const { t } = useI18n();
  const [value, setValue] = useState('');
  function ask(q: string) {
    const question = q.trim();
    if (!question) return;
    window.dispatchEvent(new CustomEvent('immo:ask', { detail: question }));
    document.getElementById('terminal')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setValue('');
  }
  return (
    <div className="w-full max-w-xl">
      <form onSubmit={(e) => { e.preventDefault(); ask(value); }} className="flex items-center gap-2 rounded-full border border-immo-dark/25 bg-white p-2 pl-6 shadow-sm focus-within:border-immo-cyan dark:border-white/20 dark:bg-white/10">
        <label htmlFor="hero-ask" className="sr-only">{t.prompt.label}</label>
        <input id="hero-ask" value={value} onChange={(e) => setValue(e.target.value)} maxLength={600} placeholder={t.prompt.placeholder}
          className="min-w-0 flex-1 bg-transparent py-2 text-lg text-immo-dark placeholder:text-immo-dark/60 focus:outline-none dark:text-white dark:placeholder:text-white/70" />
        <button type="submit" aria-label={t.prompt.send} disabled={!value.trim()} className="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-immo-cyan text-immo-dark transition-colors hover:bg-immo-sky disabled:opacity-50"><ArrowUp size={20} /></button>
      </form>
      <ul className="mt-4 flex flex-wrap gap-2">
        {t.agent.quick.map((q) => (
          <li key={q}><button type="button" onClick={() => ask(q)} className="rounded-full border border-immo-dark/20 px-4 py-2 text-sm font-medium text-immo-dark transition-colors hover:bg-immo-sky/50 dark:border-white/25 dark:text-immo-satin dark:hover:bg-white/10">{q}</button></li>
        ))}
      </ul>
    </div>
  );
}
