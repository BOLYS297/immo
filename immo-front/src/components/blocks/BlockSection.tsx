import Image from 'next/image';
import type { Background, Look, Spacing } from '@/lib/blocks';

/**
 * Fond d'un bloc générique, dans les styles déjà présents sur le site :
 * satin = fond de page, white = carte (comme les cartes Tech Insights), sky = bandeau des piliers,
 * dark = bandeau Bleu Nuit (comme le manifeste et l'appel à l'action).
 * Image de fond (médiathèque) : panneau arrondi comme les bandeaux, image recadrée, voile pour la lisibilité.
 */
const OUTER: Record<Background, string> = {
  satin: 'px-6 py-20 lg:py-28',
  white: 'px-6 py-12 lg:py-16',
  sky: 'mx-3 my-6 rounded-[2.5rem] bg-immo-sky px-6 py-20 sm:mx-6 lg:py-28 dark:bg-black/25',
  dark: 'mx-3 my-6 rounded-[2.5rem] bg-immo-dark px-6 py-20 text-white sm:mx-6 lg:py-28 dark:border dark:border-white/15 dark:bg-black/30',
};
// « compact » : marges réduites, pour enchaîner les blocs d'une page de contenu (titre, chiffres, liste…).
const COMPACT: Record<Background, string> = {
  satin: 'px-6 py-8 lg:py-10',
  white: 'px-6 py-6 lg:py-8',
  sky: 'mx-3 my-4 rounded-[2.5rem] bg-immo-sky px-6 py-12 sm:mx-6 lg:py-16 dark:bg-black/25',
  dark: 'mx-3 my-4 rounded-[2.5rem] bg-immo-dark px-6 py-12 text-white sm:mx-6 lg:py-16 dark:border dark:border-white/15 dark:bg-black/30',
};
const INNER: Record<Background, string> = {
  satin: 'mx-auto max-w-7xl',
  white: 'mx-auto max-w-7xl rounded-3xl border border-immo-dark/15 bg-white/70 p-8 lg:p-14 dark:border-white/15 dark:bg-white/5',
  sky: 'mx-auto max-w-7xl',
  dark: 'mx-auto max-w-7xl',
};
const IMAGE_PANEL: Record<Spacing, string> = {
  normal: 'relative isolate mx-3 my-6 overflow-hidden rounded-[2.5rem] px-6 py-20 sm:mx-6 lg:py-28',
  compact: 'relative isolate mx-3 my-4 overflow-hidden rounded-[2.5rem] px-6 py-12 sm:mx-6 lg:py-16',
};
const OVERLAY = { dark: 'bg-immo-dark/75', light: 'bg-immo-satin/85 dark:bg-immo-dark/80', none: '' };

/** Texte blanc : fond Bleu Nuit, ou image de fond sous un voile Bleu Nuit. */
export const isInverted = (look?: Partial<Look>) => (look?.background_image ? (look.overlay ?? 'dark') === 'dark' : look?.background === 'dark');

/** Classes de texte selon le fond : Bleu Nuit sur fond clair, blanc sur fond Bleu Nuit (et en mode sombre). */
export function tone(inverted: boolean) {
  return inverted
    ? { title: 'text-white', body: 'text-immo-satin/90', muted: 'text-immo-satin/75', card: 'border-white/15 bg-white/5', line: 'border-white/20' }
    : { title: 'text-immo-dark dark:text-white', body: 'text-immo-dark/85 dark:text-immo-satin/90', muted: 'text-immo-dark/70 dark:text-immo-satin/70', card: 'border-immo-dark/15 bg-white/70 dark:border-white/15 dark:bg-white/5', line: 'border-immo-dark/15 dark:border-white/15' };
}

export default function BlockSection({ look, labelledBy, children }: { look?: Partial<Look>; labelledBy?: string; children: React.ReactNode }) {
  const background = look?.background ?? 'satin';
  const spacing = look?.spacing ?? 'normal';
  const image = look?.background_image;

  if (image) {
    const overlay = look?.overlay ?? 'dark';
    return (
      <section aria-labelledby={labelledBy} className={`${IMAGE_PANEL[spacing]} ${overlay === 'dark' ? 'text-white' : ''}`}>
        <Image src={image.url} alt="" fill sizes="100vw" className="-z-20 object-cover" />
        {OVERLAY[overlay] && <div aria-hidden className={`absolute inset-0 -z-10 ${OVERLAY[overlay]}`} />}
        <div className="mx-auto max-w-7xl">{children}</div>
      </section>
    );
  }

  const outer = spacing === 'compact' ? COMPACT : OUTER;
  return (
    <section aria-labelledby={labelledBy} className={outer[background] ?? outer.satin}>
      <div className={INNER[background] ?? INNER.satin}>{children}</div>
    </section>
  );
}
