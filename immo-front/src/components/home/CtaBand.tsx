import type { BlockContent } from '@/lib/blocks';
import { getDict } from '@/lib/locale';
import { ButtonRow, buttonsOf } from '@/components/blocks/Buttons';

export default function CtaBand({ content }: { content?: BlockContent<'cta_band'> }) {
  const c = { ...getDict().cta, ...content };
  // Boutons saisis dans l'admin ; sinon les deux boutons par défaut (ou ceux d'un ancien contenu non converti).
  const buttons = content?.buttons?.length
    ? buttonsOf(content.buttons)
    : [{ label: c.primary, href: c.primary_href || '/contact' }, { label: c.secondary, href: c.secondary_href || '/services' }];
  return (
    <section aria-labelledby="cta-title" className="px-6 py-28">
      <div className="relative mx-auto max-w-7xl overflow-hidden rounded-3xl bg-immo-dark p-10 text-immo-satin dark:border dark:border-white/15 dark:bg-black/30 lg:p-20">
        <div aria-hidden className="pointer-events-none absolute -bottom-24 -right-10 h-[28rem] w-[28rem] bg-contain bg-no-repeat opacity-[0.12]" style={{ backgroundImage: 'url(/images/symbol-mark.png)' }} />
        <h2 id="cta-title" className="relative max-w-2xl text-4xl font-semibold leading-[1.05] tracking-tighter text-white sm:text-6xl">{c.title}</h2>
        <p className="relative mt-6 max-w-xl text-lg text-immo-satin/90">{c.text}</p>
        <ButtonRow buttons={buttons} inverted className="relative mt-10" />
      </div>
    </section>
  );
}
