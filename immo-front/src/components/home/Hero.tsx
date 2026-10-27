import Reveal from '@/components/ui/Reveal';
import type { BlockContent } from '@/lib/blocks';
import { getDict, getLocale } from '@/lib/locale';
import { loadSite } from '@/server/site';
import HeroPrompt from './HeroPrompt';
import SymbolField from './SymbolField';

export default async function Hero({ content }: { content?: BlockContent<'hero'> }) {
  const h = { ...getDict().hero, ...content };
  const { logoAnimation } = await loadSite(getLocale());
  return (
    <section aria-labelledby="hero-title">
      <div className="mx-auto grid max-w-7xl items-center gap-12 px-6 pb-28 pt-16 lg:grid-cols-[1.15fr_0.85fr] lg:pt-28">
        <div>
          <Reveal><p className="font-medium text-immo-dark/80 dark:text-immo-sky">{h.eyebrow}</p></Reveal>
          <Reveal delay={0.08}>
            <h1 id="hero-title" className="mt-5 max-w-[16ch] text-5xl font-semibold leading-[1.04] tracking-tighter text-immo-dark sm:text-6xl lg:text-7xl dark:text-white">{h.title}</h1>
          </Reveal>
          <Reveal delay={0.16}><p className="mt-7 max-w-xl text-lg text-immo-dark/85 dark:text-immo-satin/90">{h.text}</p></Reveal>
          <Reveal delay={0.24} className="mt-10"><HeroPrompt /></Reveal>
        </div>
        <div className="relative mx-auto aspect-square w-full max-w-[520px]"><SymbolField alwaysAnimate={logoAnimation === 'always'} /></div>
      </div>
    </section>
  );
}
