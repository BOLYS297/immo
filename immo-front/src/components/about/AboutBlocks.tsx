import Link from 'next/link';
import { BrainCircuit, Code2, PenTool, ServerCog, type LucideIcon } from 'lucide-react';
import type { BlockContent } from '@/lib/blocks';
import { getDict } from '@/lib/locale';

/**
 * Blocs de la page À propos (about_intro, team, commitments). Un champ vide reprend le texte du dictionnaire.
 * Les sections sont espacées de 7 rem, comme l'ancienne page (space-y-28).
 */
const MEMBER_ICONS: LucideIcon[] = [BrainCircuit, Code2, PenTool, ServerCog];
const H2 = 'text-4xl font-semibold tracking-tighter text-immo-dark sm:text-5xl dark:text-white';
const BODY = 'text-immo-dark/85 dark:text-immo-satin/90';

/** Titre principal (h1), introduction et définitions. */
export function AboutIntro({ id, content: c = {} }: { id: string; content?: BlockContent<'about_intro'> }) {
  const a = getDict().pages.about;
  const items = c.items?.length ? c.items : a.meaning;
  return (
    <section aria-labelledby={`${id}-t`} className="px-6 pb-14 pt-16 lg:pt-24">
      <div className="mx-auto max-w-7xl">
        <h1 id={`${id}-t`} className="max-w-4xl text-5xl font-semibold leading-[1.04] tracking-tighter text-immo-dark sm:text-6xl lg:text-7xl dark:text-white">{c.title || a.title}</h1>
        <p className={`mt-6 max-w-2xl text-lg ${BODY}`}>{c.intro || a.intro}</p>
        <dl className="mt-12 grid gap-8 md:grid-cols-3">
          {items.map((m, i) => (
            <div key={i} className="border-t-4 border-immo-cyan pt-5">
              <dt className="text-xl font-semibold text-immo-dark dark:text-white">{m.title}</dt>
              <dd className={`mt-2 ${BODY}`}>{m.text}</dd>
            </div>
          ))}
        </dl>
      </div>
    </section>
  );
}

/** Histoire de l'équipe (paragraphes séparés par une ligne vide) et membres / pôles. */
export function Team({ id, content: c = {} }: { id: string; content?: BlockContent<'team'> }) {
  const a = getDict().pages.about;
  const paragraphs = c.body ? c.body.split(/\n\s*\n/).map((p) => p.trim()).filter(Boolean) : [a.story1, a.story2];
  const members = c.members?.length ? c.members : a.founders.map((f) => ({ title: f.pole, text: f.text }));
  return (
    <section aria-labelledby={`${id}-t`} className="px-6 py-14">
      <div className="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[0.9fr_1.1fr]">
        <div>
          <h2 id={`${id}-t`} className={H2}>{c.title || a.storyTitle}</h2>
          {paragraphs.map((p, i) => <p key={i} className={`${i === 0 ? 'mt-5' : 'mt-4'} ${BODY}`}>{p}</p>)}
        </div>
        <ul className="grid gap-5 sm:grid-cols-2">
          {members.map((m, i) => {
            const Icon = MEMBER_ICONS[i % MEMBER_ICONS.length];
            return (
              <li key={i} className="border-t border-immo-dark/30 pt-5 dark:border-white/30">
                <Icon size={28} className="text-immo-dark dark:text-immo-cyan" aria-hidden />
                <h3 className="mt-4 text-lg font-semibold text-immo-dark dark:text-white">{m.title}</h3>
                <p className={`mt-2 ${BODY}`}>{m.text}</p>
              </li>
            );
          })}
        </ul>
      </div>
    </section>
  );
}

/** Engagements numérotés et bouton d'action. */
export function Commitments({ id, content: c = {} }: { id: string; content?: BlockContent<'commitments'> }) {
  const t = getDict();
  const a = t.pages.about;
  const items = c.items?.length ? c.items : t.pillars.items;
  const button = c.button || a.meet;
  return (
    <section aria-labelledby={`${id}-t`} className="px-6 pb-16 pt-14 lg:pb-24">
      <div className="mx-auto max-w-7xl">
        <h2 id={`${id}-t`} className={H2}>{c.title || a.commitTitle}</h2>
        <p className={`mt-4 max-w-2xl ${BODY}`}>{c.intro || a.commitIntro}</p>
        <ul className="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
          {items.map((p, i) => (
            <li key={i} className="border-t-2 border-immo-dark pt-6 dark:border-immo-cyan">
              <p className="text-sm font-semibold text-immo-dark/70 dark:text-immo-sky">{String(i + 1).padStart(2, '0')}</p>
              <h3 className="mt-3 text-xl font-semibold text-immo-dark dark:text-white">{p.title}</h3>
              <p className={`mt-2 ${BODY}`}>{p.text}</p>
            </li>
          ))}
        </ul>
        {button && <Link href={c.href || '/contact'} className="mt-12 inline-block rounded-full bg-immo-cyan px-7 py-3.5 font-semibold text-immo-dark transition-colors hover:bg-immo-sky">{button}</Link>}
      </div>
    </section>
  );
}
