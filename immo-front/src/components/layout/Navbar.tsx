import Link from 'next/link';
import Logo from '@/components/ui/Logo';
import ThemeToggle from './ThemeToggle';
import LanguageSwitcher from './LanguageSwitcher';
import MobileMenu from './MobileMenu';
import SiteLink from './SiteLink';
import { getDict, getLocale } from '@/lib/locale';
import { loadSite } from '@/server/site';

export default async function Navbar() {
  const t = getDict();
  const site = await loadSite(getLocale());
  const links = site.header.filter((l) => l.display !== 'mobile');
  return (
    <header className="sticky top-0 z-40 border-b border-immo-dark/10 bg-immo-satin/85 backdrop-blur dark:border-white/10 dark:bg-immo-dark/85">
      <a href="#contenu" className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-2 focus:rounded focus:bg-immo-cyan focus:px-3 focus:py-1 focus:text-immo-dark">{t.a11y.skip}</a>
      <div className="mx-auto flex h-[72px] max-w-7xl items-center justify-between gap-6 px-6">
        <Link href="/" aria-label={t.a11y.home} className="shrink-0 text-immo-dark dark:text-white"><Logo priority /></Link>
        {links.length > 0 && (
          <nav aria-label={t.a11y.mainNav} className="hidden items-center gap-8 lg:flex">
            {links.map((l) => <SiteLink key={`${l.url}-${l.label}`} url={l.url} newTab={l.newTab} className="font-medium transition-colors hover:text-immo-cyan">{l.label}</SiteLink>)}
          </nav>
        )}
        <div className="flex items-center gap-3">
          {site.cta && <SiteLink url={site.cta.url} className="hidden rounded-full bg-immo-cyan px-5 py-2.5 font-semibold text-immo-dark transition-colors hover:bg-immo-sky sm:inline-block">{site.cta.label}</SiteLink>}
          <div className="hidden sm:block"><LanguageSwitcher /></div>
          <ThemeToggle />
          <MobileMenu links={site.header.filter((l) => l.display !== 'desktop')} cta={site.cta} />
        </div>
      </div>
    </header>
  );
}
