'use client';
import { useEffect, useMemo, useState } from 'react';
import { ArrowLeft, CalendarCheck, CheckCircle2, Loader2 } from 'lucide-react';
import { useI18n } from '@/components/I18nProvider';
import FormNotice from '@/components/legal/FormNotice';
import { DEADLINE_KEYS, NEED_KEYS, type DeadlineKey, type NeedKey } from '@/lib/booking';

const TZ = 'Africa/Douala';
const fmt = (iso: string, tag: string, o: Intl.DateTimeFormatOptions) => new Intl.DateTimeFormat(tag, { ...o, timeZone: TZ }).format(new Date(iso));
const choice = 'w-full rounded-2xl border border-immo-dark/20 bg-white/70 px-6 py-5 text-left text-lg font-semibold transition-colors hover:border-immo-cyan hover:bg-immo-sky/40 aria-checked:border-immo-cyan aria-checked:bg-immo-sky/60 dark:border-white/20 dark:bg-white/5 dark:hover:bg-white/10 dark:aria-checked:bg-white/15';
const field = 'mt-1.5 w-full rounded-xl border border-immo-dark/25 bg-white px-4 py-3 text-immo-dark placeholder:text-immo-dark/60 dark:border-white/20 dark:bg-white/10 dark:text-white dark:placeholder:text-white/70';

export default function Funnel() {
  const { t, locale } = useI18n();
  const c = t.pages.contact;
  const tag = locale === 'fr' ? 'fr-FR' : 'en-GB';
  const [step, setStep] = useState<1 | 2 | 3>(1);
  const [need, setNeed] = useState<NeedKey | null>(null);
  const [deadline, setDeadline] = useState<DeadlineKey | null>(null);
  const [slots, setSlots] = useState<string[] | null>(null);
  const [day, setDay] = useState<string | null>(null);
  const [slot, setSlot] = useState<string | null>(null);
  const [status, setStatus] = useState<'idle' | 'sending' | 'done' | 'error'>('idle');
  const [error, setError] = useState('');

  useEffect(() => {
    if (step !== 3 || slots) return;
    fetch('/api/booking')
      .then((r) => r.json() as Promise<{ slots: string[] }>)
      .then((d) => { setSlots(d.slots); setDay(d.slots[0]?.slice(0, 10) ?? null); })
      .catch(() => { setSlots([]); setError(c.slotsError); });
  }, [step, slots, c.slotsError]);

  const days = useMemo(() => {
    const m = new Map<string, string[]>();
    for (const s of slots ?? []) m.set(s.slice(0, 10), [...(m.get(s.slice(0, 10)) ?? []), s]);
    return [...m.entries()];
  }, [slots]);

  const pole = need ? c.needs[NEED_KEYS.indexOf(need)].pole : '';

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    if (!need || !deadline || !slot) return;
    setStatus('sending');
    const form = Object.fromEntries(new FormData(e.currentTarget).entries());
    try {
      const res = await fetch('/api/booking', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ...form, need, deadline, slot }) });
      const out = (await res.json()) as { error?: string };
      if (!res.ok) throw new Error(out.error ?? c.failed);
      setStatus('done');
    } catch (err) {
      setError(err instanceof Error ? err.message : c.failed);
      setStatus('error');
    }
  }

  if (status === 'done' && slot) {
    const text = c.doneText.replace('{date}', fmt(slot, tag, { weekday: 'long', day: 'numeric', month: 'long' })).replace('{time}', fmt(slot, tag, { hour: '2-digit', minute: '2-digit' })).replace('{pole}', pole);
    return (
      <div role="status" className="flex gap-4 rounded-2xl border border-immo-cyan bg-white/70 p-8 dark:bg-white/5">
        <CheckCircle2 size={28} className="shrink-0 text-immo-cyan" aria-hidden />
        <div><p className="text-xl font-semibold">{c.doneTitle}</p><p className="mt-2">{text}</p></div>
      </div>
    );
  }

  return (
    <div>
      <ol className="mb-8 flex flex-wrap gap-x-6 gap-y-2" aria-label={c.progress}>
        {c.stepLabels.map((l, i) => (
          <li key={l} aria-current={step === i + 1 ? 'step' : undefined} className={`font-semibold ${step === i + 1 ? 'text-immo-dark dark:text-white' : 'text-immo-dark/60 dark:text-immo-satin/60'}`}>
            <span className={`mr-2 inline-grid h-7 w-7 place-items-center rounded-full text-sm ${step >= i + 1 ? 'bg-immo-cyan text-immo-dark' : 'border border-current'}`}>{i + 1}</span>{l}
          </li>
        ))}
      </ol>

      {step === 1 && (
        <div role="radiogroup" aria-label={c.needsLabel} className="space-y-3">
          {NEED_KEYS.map((k, i) => (
            <button key={k} role="radio" aria-checked={need === k} onClick={() => { setNeed(k); setStep(2); }} className={choice}>
              {c.needs[i].label}<span className="mt-1 block text-base font-medium text-immo-dark/70 dark:text-immo-satin/70">{c.needs[i].pole}</span>
            </button>
          ))}
        </div>
      )}

      {step === 2 && (
        <div role="radiogroup" aria-label={c.deadlineLabel} className="space-y-3">
          {DEADLINE_KEYS.map((k, i) => (
            <button key={k} role="radio" aria-checked={deadline === k} onClick={() => { setDeadline(k); setStep(3); }} className={choice}>{c.deadlines[i]}</button>
          ))}
        </div>
      )}

      {step === 3 && (
        <form onSubmit={onSubmit} aria-busy={status === 'sending'} className="space-y-6">
          <p className="flex items-center gap-2 font-semibold"><CalendarCheck size={20} className="text-immo-cyan" aria-hidden /> {c.session} · {pole}</p>
          {!slots ? (
            <p className="flex items-center gap-2"><Loader2 size={18} className="animate-spin" aria-hidden /> {c.loadingSlots}</p>
          ) : (
            <>
              <div role="tablist" aria-label={c.daysLabel} className="flex flex-wrap gap-2">
                {days.map(([d, list]) => (
                  <button type="button" role="tab" key={d} aria-selected={day === d} onClick={() => { setDay(d); setSlot(null); }}
                    className={`rounded-full border px-4 py-2 font-semibold capitalize transition-colors ${day === d ? 'border-immo-cyan bg-immo-cyan text-immo-dark' : 'border-immo-dark/25 hover:bg-immo-sky/50 dark:border-white/25 dark:hover:bg-white/10'}`}>
                    {fmt(list[0], tag, { weekday: 'short', day: 'numeric', month: 'short' })}
                  </button>
                ))}
              </div>
              <div role="radiogroup" aria-label={c.slotsLabel} className="flex flex-wrap gap-2">
                {(days.find(([d]) => d === day)?.[1] ?? []).map((s) => (
                  <button type="button" role="radio" aria-checked={slot === s} key={s} onClick={() => setSlot(s)}
                    className={`rounded-xl border px-4 py-2.5 font-semibold transition-colors ${slot === s ? 'border-immo-cyan bg-immo-cyan text-immo-dark' : 'border-immo-dark/25 hover:bg-immo-sky/50 dark:border-white/25 dark:hover:bg-white/10'}`}>
                    {fmt(s, tag, { hour: '2-digit', minute: '2-digit' })}
                  </button>
                ))}
              </div>
              <p className="text-sm text-immo-dark/70 dark:text-immo-satin/70">{c.tz}</p>
            </>
          )}
          {slot && (
            <div className="grid gap-5 sm:grid-cols-2">
              <label className="font-semibold">{c.name}<input name="name" required maxLength={80} autoComplete="name" className={field} /></label>
              <label className="font-semibold">{c.email}<input name="email" type="email" required maxLength={120} autoComplete="email" className={field} /></label>
              <label className="font-semibold sm:col-span-2">{c.company}<input name="company" required maxLength={100} autoComplete="organization" className={field} /></label>
              <input name="website" tabIndex={-1} autoComplete="off" aria-hidden className="hidden" />
            </div>
          )}
          {slot && (
            <FormNotice />
          )}
          {error && <p role="alert" className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
          <button type="submit" disabled={!slot || status === 'sending'} className="inline-flex items-center gap-2 rounded-full bg-immo-cyan px-7 py-3.5 font-semibold text-immo-dark transition-colors hover:bg-immo-sky disabled:opacity-60">
            {status === 'sending' && <Loader2 size={18} className="animate-spin" aria-hidden />}{c.confirm}
          </button>
        </form>
      )}

      {step > 1 && status !== 'sending' && (
        <button type="button" onClick={() => { setError(''); setStatus('idle'); setStep((s) => (s - 1) as 1 | 2); }} className="mt-6 inline-flex items-center gap-2 font-semibold underline decoration-immo-cyan decoration-2 underline-offset-4">
          <ArrowLeft size={16} aria-hidden /> {c.back}
        </button>
      )}
    </div>
  );
}
