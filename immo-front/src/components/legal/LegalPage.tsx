import type { LegalDoc, Part } from '@/content/legal';
import { formatDate } from '@/lib/articles';
import type { Locale } from '@/lib/i18n';
import type { Contact } from '@/lib/site';

function render(p: Part, i: number, todo: string, contact: Contact) {
  if (typeof p === 'string') return <span key={i}>{p}</span>;
  const value = 'contact' in p ? (p.contact === 'email' ? contact.email : contact.phones.map((ph) => ph.label).join(' · ') || null) : p.value;
  if (value) return <strong key={i} className="font-semibold text-immo-dark dark:text-white">{value}</strong>;
  return <mark key={i} className="rounded bg-immo-sky/70 px-1.5 py-0.5 font-semibold text-immo-dark dark:bg-immo-cyan/25 dark:text-immo-sky">[{todo}]</mark>;
}

export default function LegalPage({ doc, locale, updatedLabel, contact }: { doc: LegalDoc; locale: Locale; updatedLabel: string; contact: Contact }) {
  return (
    <main id="contenu" className="px-6 py-16 lg:py-24">
      <article className="mx-auto max-w-3xl">
        <h1 className="text-5xl font-semibold leading-[1.04] tracking-tighter text-immo-dark sm:text-6xl dark:text-white">{doc.title}</h1>
        <p className="mt-4 text-sm text-immo-dark/70 dark:text-immo-satin/70">{updatedLabel} <time dateTime={doc.updated}>{formatDate(doc.updated, locale)}</time></p>
        <p className="mt-6 text-lg text-immo-dark/85 dark:text-immo-satin/90">{doc.intro}</p>
        <div className="mt-12 space-y-10">
          {doc.sections.map((s) => (
            <section key={s.title} className="border-t border-immo-dark/15 pt-6 dark:border-white/15">
              <h2 className="text-2xl font-semibold tracking-tight text-immo-dark dark:text-white">{s.title}</h2>
              <div className="mt-4 space-y-2.5 leading-7 text-immo-dark/85 dark:text-immo-satin/90">
                {s.body.map((line, i) => <p key={i}>{line.map((p, j) => render(p, j, doc.todo, contact))}</p>)}
              </div>
            </section>
          ))}
        </div>
      </article>
    </main>
  );
}
