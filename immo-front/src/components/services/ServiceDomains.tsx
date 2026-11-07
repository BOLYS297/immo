import Image from 'next/image';
import { AppWindow, ShieldCheck, Workflow, type LucideIcon } from 'lucide-react';
import type { BlockContent, BlockSettings, MediaImage } from '@/lib/blocks';
import { getDict, getLocale } from '@/lib/locale';
import { lucideIcon } from '@/lib/lucide';
import { DOMAIN_KEYS } from '@/lib/services-data';
import { getServices, type ServiceView } from '@/server/cms-api';
import { ButtonRow, buttonsOf } from '@/components/blocks/Buttons';

const DEFAULT_ICONS: LucideIcon[] = [Workflow, AppWindow, ShieldCheck];

/** Icône d'un service : image importée dans l'admin, sinon icône Lucide d'après son nom (repli : Workflow). */
const serviceIcon = (x: ServiceView): LucideIcon | MediaImage => x.iconImage ?? lucideIcon(x.icon) ?? Workflow;

type Domain = { title?: string | null; benefit?: string | null; deliverables: string[]; buttons: { label: string; href: string }[]; icon: LucideIcon | MediaImage };

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
    domains = services.map((x) => ({ title: x.title, benefit: x.description, deliverables: x.deliverables, buttons: x.cta ? [{ label: x.cta, href: quoteHref(x.domain) }] : [], icon: serviceIcon(x) }));
  }
  if (domains.length === 0) {
    const manual = c.domains?.length
      ? c.domains.map((d) => ({ ...d, deliverables: (d.deliverables ?? '').split('\n').map((x) => x.trim()).filter(Boolean) }))
      : s.domains;
    // Ancien format (cta + href) et textes du dictionnaire : un seul bouton vers le chiffrage du pôle.
    domains = manual.map((d: { title?: string; benefit?: string; deliverables: string[]; buttons?: { label?: string; href?: string }[]; cta?: string; href?: string }, i) => ({
      title: d.title, benefit: d.benefit, deliverables: d.deliverables,
      buttons: buttonsOf(d.buttons, { label: d.cta, href: d.href || quoteHref(DOMAIN_KEYS[i] ?? 'web') }),
      icon: DEFAULT_ICONS[i % DEFAULT_ICONS.length],
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
                {'url' in Icon
                  ? <Image src={Icon.url} width={Icon.width} height={Icon.height} alt="" sizes="32px" className="size-8 object-contain" />
                  : <Icon size={32} className="text-immo-dark dark:text-immo-cyan" aria-hidden />}
                <h3 className="mt-5 text-xl font-semibold text-immo-dark dark:text-white">{d.title}</h3>
                <p className="mt-2 text-immo-dark/85 dark:text-immo-satin/90">{d.benefit}</p>
                {d.deliverables.length > 0 && (
                  <>
                    <p className="mt-5 font-semibold">{c.deliverables_label || s.deliverables}</p>
                    <ul className="mt-2 flex-1 list-disc space-y-1 pl-5 marker:text-immo-cyan">{d.deliverables.map((x) => <li key={x}>{x}</li>)}</ul>
                  </>
                )}
                <ButtonRow buttons={d.buttons} className="mt-7 flex-col [&>a]:px-5 [&>a]:py-3" />
              </article>
            );
          })}
        </div>
      </div>
    </section>
  );
}
