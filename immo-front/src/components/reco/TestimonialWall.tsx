import Image from 'next/image';
import { BadgeCheck } from 'lucide-react';
import { getDict, getLocale } from '@/lib/locale';
import { getTestimonials, type TestimonialView } from '@/server/cms-api';
import { initials } from '@/lib/initials';

/**
 * Avis publiés dans l'admin Laravel (Avis & Témoignages), dans la langue du visiteur sinon en français.
 * `items` : avis déjà chargés (bloc « Mur d'avis ») ; sinon chargés ici.
 */

export default async function TestimonialWall({ items: given }: { items?: TestimonialView[] }) {
  const r = getDict().pages.reco;
  const items = given ?? (await getTestimonials(getLocale()));
  if (items.length === 0) return <p className="mt-8 rounded-2xl border border-immo-dark/15 p-7 dark:border-white/15">{r.empty}</p>;
  return (
    <ul className="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      {items.map((t) => {
        const { quote, metric } = t;
        return (
          <li key={t.id} className="rounded-2xl border border-immo-dark/15 bg-white/70 p-7 dark:border-white/15 dark:bg-white/5">
            <figure className="flex h-full flex-col">
            <div className="flex items-center justify-between gap-3">
              {t.pole ? <span className="rounded-full bg-immo-sky/60 px-3 py-1 text-sm font-semibold text-immo-dark dark:bg-white/10 dark:text-immo-satin">{t.pole}</span> : <span />}
              <span className="inline-flex items-center gap-1.5 text-sm font-semibold text-immo-dark dark:text-immo-sky"><BadgeCheck size={18} className="text-immo-cyan" aria-hidden /> {r.verified}</span>
            </div>
            <blockquote className="mt-5 flex-1 text-lg">{quote}</blockquote>
            {metric && <p className="mt-5 font-semibold text-immo-dark dark:text-white">{metric}</p>}
            <figcaption className="mt-4 flex items-center gap-3 border-t border-immo-dark/10 pt-4 dark:border-white/10">
              {/* Photo de la personne (médiathèque), sinon ses initiales ; décorative : le nom suit */}
              {t.photo
                ? <Image src={t.photo.url} width={48} height={48} alt="" sizes="48px" className="h-12 w-12 shrink-0 rounded-full object-cover" />
                : <span aria-hidden className="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-immo-sky font-semibold text-immo-dark dark:bg-white/10 dark:text-immo-satin">{initials(t.name)}</span>}
              <span>
                <span className="block font-semibold">{t.name}</span>
                <span className="block text-sm text-immo-dark/80 dark:text-immo-satin/80">{t.role}{t.role && t.company ? ', ' : ''}{t.company}</span>
              </span>
            </figcaption>
            </figure>
          </li>
        );
      })}
    </ul>
  );
}
