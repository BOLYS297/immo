import Link from 'next/link';
import { AppWindow, BrainCircuit, ChartLine, Cloud, Code2, PenTool, ServerCog, ShieldCheck, Smartphone, Workflow, type LucideIcon } from 'lucide-react';
import type { BlockContent, BlockSettings } from '@/lib/blocks';
import { getDict, getLocale } from '@/lib/locale';
import { DOMAIN_KEYS } from '@/lib/services-data';
import { getServices } from '@/server/cms-api';

/** Icônes proposées dans l'admin (immo-back config/cms.php, service_icons). */
const ICONS: Record<string, LucideIcon> = {
  workflow: Workflow, 'brain-circuit': BrainCircuit, 'app-window': AppWindow, smartphone: Smartphone, code: Code2,
  'pen-tool': PenTool, 'server-cog': ServerCog, cloud: Cloud, 'shield-check': ShieldCheck, 'chart-line': ChartLine,
};
const DEFAULT_ICONS: LucideIcon[] = [Workflow, AppWindow, ShieldCheck];

type Domain = { title?: string | null; benefit?: string | null; deliverables: string[]; cta?: string | null; href: string; icon: LucideIcon };

/**
 * Domaines d'expertise (bloc « service_domains »). Source « services » : services publiés dans l'admin ;
 * sinon (ou si aucun n'est publié) les domaines saisis dans le bloc, puis les textes du dictionnaire.
 */
export default async function ServiceDomains({ content: c = {}, settings }: { content?: BlockContent<'service_domains'>; settings?: BlockSettings<'service_domains'> }) {
  const s = getDict().pages.services;
  const quoteHref = (key?: string | null) => (key ? `/services?domaine=${key}#chiffrage` : '/contact');

  let domains: Domain[] = [];
  if (settings?.source === 'services') {
    const services = await getServices(getLocale()).catch(() => []);
    domains = services.map((x) => ({ title: x.title, benefit: x.description, deliverables: x.deliverables, cta: x.cta, href: quoteHref(x.domain), icon: ICONS[x.icon] ?? Workflow }));
  }
  if (domains.length === 0) {
    const manual = c.domains?.length
      ? c.domains.map((d) => ({ ...d, deliverables: (d.deliverables ?? '').split('\n').map((x) => x.trim()).filter(Boolean) }))
      : s.domains;
    domains = manual.map((d: { title?: string; benefit?: string; deliverables: string[]; cta?: string; href?: string }, i) => ({
      ...d, href: d.href || quoteHref(DOMAIN_KEYS[i] ?? 'web'), icon: DEFAULT_ICONS[i % DEFAULT_ICONS.length],
    }));
  }

  return (
    <section aria-labelledby="domaines-title" className="px-6 py-16">
      <div className="mx-auto max-w-7xl">
        <h2 id="domaines-title" className="text-4xl font-semibold tracking-tighter text-immo-dark sm:text-5xl dark:text-white">{c.title || s.domainsTitle}</h2>
        <div className="mt-10 grid gap-8 lg:grid-cols-3">
          {domains.map((d, i) => {
            const Icon = d.icon;
            return (
              <article key={i} className="flex flex-col border-t-2 border-immo-dark pt-6 dark:border-immo-cyan">
                <Icon size={32} className="text-immo-dark dark:text-immo-cyan" aria-hidden />
                <h3 className="mt-5 text-xl font-semibold text-immo-dark dark:text-white">{d.title}</h3>
                <p className="mt-2 text-immo-dark/85 dark:text-immo-satin/90">{d.benefit}</p>
                {d.deliverables.length > 0 && (
                  <>
                    <p className="mt-5 font-semibold">{c.deliverables_label || s.deliverables}</p>
                    <ul className="mt-2 flex-1 list-disc space-y-1 pl-5 marker:text-immo-cyan">{d.deliverables.map((x) => <li key={x}>{x}</li>)}</ul>
                  </>
                )}
                {d.cta && <Link href={d.href} className="mt-7 rounded-full bg-immo-cyan px-5 py-3 text-center font-semibold text-immo-dark transition-colors hover:bg-immo-sky">{d.cta}</Link>}
              </article>
            );
          })}
        </div>
      </div>
    </section>
  );
}
