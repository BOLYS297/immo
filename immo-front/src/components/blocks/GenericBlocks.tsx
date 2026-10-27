import Image from 'next/image';
import Link from 'next/link';
import { ArrowUpRight, ChevronDown, ShieldCheck } from 'lucide-react';
import RichText from '@/components/RichText';
import { jsonLd, plainText } from '@/lib/seo';
import type { BlockContent, BlockSettings, MediaImage } from '@/lib/blocks';
import BlockSection, { isInverted, tone } from './BlockSection';
import { initials } from '@/lib/initials';

/**
 * Blocs génériques du CMS (config/cms.php, groupe « Blocs génériques »), utilisables sur toutes les pages.
 * Un bloc sans contenu ne s'affiche pas. `id` sert à relier le titre à sa section (aria-labelledby).
 */
type P<K extends 'text' | 'media_text' | 'cards' | 'stats' | 'faq' | 'page_header' | 'callout' | 'quote_panel' | 'team_members'> = { id: string; content: BlockContent<K>; settings?: BlockSettings<K> };

/** Image de la médiathèque, redimensionnée par next/image selon la place qu'elle occupe (sizes). */
function CmsImage({ image, sizes, className }: { image: MediaImage; sizes: string; className?: string }) {
  return <Image src={image.url} width={image.width} height={image.height} alt={image.alt} sizes={sizes} className={className} />;
}

const H2 = 'text-4xl font-semibold tracking-tighter sm:text-5xl';
const linkClass = 'font-semibold underline decoration-immo-cyan decoration-2 underline-offset-4';

export function TextBlock({ id, content: c, settings: s }: P<'text'>) {
  if (!c.title && !c.body && !c.eyebrow) return null;
  const inv = isInverted(s), t = tone(inv), center = s?.align === 'center';
  return (
    <BlockSection look={s} labelledBy={c.title ? `${id}-t` : undefined}>
      <div className={center ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl'}>
        {c.eyebrow && <p className={`font-medium ${inv ? 'text-immo-sky' : 'text-immo-dark/80 dark:text-immo-sky'}`}>{c.eyebrow}</p>}
        {c.title && <h2 id={`${id}-t`} className={`${c.eyebrow ? 'mt-4' : ''} ${H2} ${t.title}`}>{c.title}</h2>}
        {c.body && <RichText body={c.body} inverted={inv} className={`${c.title ? 'mt-6' : ''} space-y-4 text-lg ${t.body} ${center ? '[&_ul]:text-left' : ''}`} />}
      </div>
    </BlockSection>
  );
}

export function MediaTextBlock({ id, content: c, settings: s }: P<'media_text'>) {
  if (!c.image && !c.title && !c.body) return null;
  const inv = isInverted(s), t = tone(inv);
  return (
    <BlockSection look={s} labelledBy={c.title ? `${id}-t` : undefined}>
      <div className="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
        {c.image && (
          <div className={s?.image_position === 'left' ? 'lg:order-first' : 'lg:order-last'}>
            <CmsImage image={c.image} sizes="(min-width: 1024px) 50vw, 100vw" className="h-auto w-full rounded-3xl" />
          </div>
        )}
        <div>
          {c.eyebrow && <p className={`font-medium ${inv ? 'text-immo-sky' : 'text-immo-dark/80 dark:text-immo-sky'}`}>{c.eyebrow}</p>}
          {c.title && <h2 id={`${id}-t`} className={`${c.eyebrow ? 'mt-4' : ''} ${H2} ${t.title}`}>{c.title}</h2>}
          {c.body && <RichText body={c.body} inverted={inv} className={`${c.title ? 'mt-6' : ''} space-y-4 text-lg ${t.body}`} />}
          {c.href && c.link_label && (
            <Link href={c.href} className="mt-8 inline-block rounded-full bg-immo-cyan px-7 py-3.5 font-semibold text-immo-dark transition-colors hover:bg-immo-sky">{c.link_label}</Link>
          )}
        </div>
      </div>
    </BlockSection>
  );
}

const COLS = { '2': 'md:grid-cols-2', '3': 'md:grid-cols-2 lg:grid-cols-3', '4': 'sm:grid-cols-2 lg:grid-cols-4' } as const;

export function CardsBlock({ id, content: c, settings: s }: P<'cards'>) {
  const items = (c.items ?? []).filter((it) => it.title || it.text || it.image);
  if (!c.title && items.length === 0) return null;
  const inv = isInverted(s), t = tone(inv);
  const cols = s?.columns ?? '3';
  return (
    <BlockSection look={s} labelledBy={c.title ? `${id}-t` : undefined}>
      {c.title && <h2 id={`${id}-t`} className={`max-w-3xl ${H2} ${t.title}`}>{c.title}</h2>}
      {c.intro && <p className={`mt-5 max-w-2xl text-lg ${t.body}`}>{c.intro}</p>}
      {items.length > 0 && (
        <ul className={`mt-12 grid gap-6 ${COLS[cols] ?? COLS['3']}`}>
          {items.map((it, i) => (
            <li key={i} className={`flex flex-col overflow-hidden rounded-2xl border p-7 transition-colors hover:border-immo-cyan ${t.card}`}>
              {it.image && (
                <CmsImage image={it.image} sizes={`(min-width: 1024px) ${Math.round(100 / Number(cols))}vw, (min-width: 768px) 50vw, 100vw`} className="-mx-7 -mt-7 mb-6 aspect-[4/3] w-[calc(100%+3.5rem)] max-w-none object-cover" />
              )}
              {it.title && <h3 className={`text-xl font-semibold ${t.title}`}>{it.title}</h3>}
              {it.text && <RichText body={it.text} inverted={inv} className={`mt-3 flex-1 space-y-3 ${t.body}`} />}
              {it.href && (
                <Link href={it.href} className={`mt-5 inline-flex items-center gap-1.5 ${linkClass} ${inv ? 'text-white' : ''}`}>
                  {it.link_label || it.title} <ArrowUpRight size={16} aria-hidden />
                </Link>
              )}
            </li>
          ))}
        </ul>
      )}
    </BlockSection>
  );
}

export function StatsBlock({ id, content: c, settings: s }: P<'stats'>) {
  const items = (c.items ?? []).filter((it) => it.value);
  if (items.length === 0) return null;
  const inv = isInverted(s), t = tone(inv);
  return (
    <BlockSection look={s} labelledBy={c.title ? `${id}-t` : undefined}>
      {c.title && <h2 id={`${id}-t`} className={`max-w-3xl ${H2} ${t.title}`}>{c.title}</h2>}
      <dl className={`${c.title ? 'mt-12' : ''} grid gap-8 border-y py-10 sm:grid-cols-2 ${items.length >= 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-3'} ${t.line}`}>
        {items.map((it, i) => (
          <div key={i} className="flex flex-col-reverse">
            <dd className={`mt-2 max-w-[24ch] ${t.body}`}>{it.label}</dd>
            <dt className={`text-5xl font-semibold tracking-tighter ${t.title}`}>{it.value}</dt>
          </div>
        ))}
      </dl>
    </BlockSection>
  );
}

export function FaqBlock({ id, content: c, settings: s }: P<'faq'>) {
  const items = (c.items ?? []).filter((it) => it.question && it.answer);
  if (items.length === 0) return null;
  const inv = isInverted(s), t = tone(inv);
  return (
    <BlockSection look={s} labelledBy={c.title ? `${id}-t` : undefined}>
      {c.title && <h2 id={`${id}-t`} className={`max-w-3xl ${H2} ${t.title}`}>{c.title}</h2>}
      <div className={`${c.title ? 'mt-12' : ''} max-w-4xl border-b ${t.line}`}>
        {items.map((it, i) => (
          <details key={i} className={`group border-t ${t.line}`}>
            <summary className={`flex cursor-pointer list-none items-center justify-between gap-6 py-6 text-xl font-semibold ${t.title} [&::-webkit-details-marker]:hidden`}>
              {it.question}
              <ChevronDown size={22} aria-hidden className="shrink-0 text-immo-cyan transition-transform duration-300 group-open:rotate-180" />
            </summary>
            <RichText body={it.answer ?? ''} inverted={inv} className={`space-y-3 pb-7 text-lg ${t.body}`} />
          </details>
        ))}
      </div>
      {/* Données structurées FAQPage : questions affichées, réponses en texte brut. */}
      <script type="application/ld+json" dangerouslySetInnerHTML={jsonLd({
        '@context': 'https://schema.org', '@type': 'FAQPage',
        mainEntity: items.map((it) => ({ '@type': 'Question', name: it.question, acceptedAnswer: { '@type': 'Answer', text: plainText(it.answer ?? '') } })),
      })} />
    </BlockSection>
  );
}

/** Titre principal de la page (h1) : à placer une seule fois, en tête de page. */
export function PageHeaderBlock({ id, content: c, settings: s }: P<'page_header'>) {
  if (!c.title) return null;
  return (
    <section aria-labelledby={`${id}-t`} className="px-6 pb-8 pt-16 lg:pt-24">
      <div className="mx-auto max-w-7xl">
        {c.eyebrow && <p className="mb-4 font-medium text-immo-dark/80 dark:text-immo-sky">{c.eyebrow}</p>}
        <h1 id={`${id}-t`} className={`max-w-4xl text-5xl font-semibold leading-[1.04] tracking-tighter text-immo-dark sm:text-6xl dark:text-white ${s?.size === 'lg' ? '' : 'lg:text-7xl'}`}>{c.title}</h1>
        {c.intro && <p className="mt-6 max-w-2xl text-lg text-immo-dark/85 dark:text-immo-satin/90">{c.intro}</p>}
      </div>
    </section>
  );
}

/** Encadré (bordure cyan) : règle, garantie, information importante, avec un lien facultatif. */
export function CalloutBlock({ id, content: c, settings: s }: P<'callout'>) {
  if (!c.title && !c.text) return null;
  return (
    <BlockSection look={s} labelledBy={c.title ? `${id}-t` : undefined}>
      <div className="flex gap-5 rounded-2xl border-l-4 border-immo-cyan bg-white/70 p-7 dark:bg-white/5">
        <ShieldCheck size={32} className="mt-1 shrink-0 text-immo-cyan" aria-hidden />
        <div>
          {c.title && <h2 id={`${id}-t`} className="text-xl font-semibold text-immo-dark dark:text-white">{c.title}</h2>}
          {c.text && <p className="mt-2 max-w-2xl text-immo-dark/85 dark:text-immo-satin/90">{c.text}</p>}
          {c.link_label && c.href && <Link href={c.href} className={`mt-5 inline-block ${linkClass}`}>{c.link_label}</Link>}
        </div>
      </div>
    </BlockSection>
  );
}

/** Citation sur bandeau Bleu Nuit : petit libellé, grande phrase, texte d'accompagnement. */
export function QuotePanelBlock({ id, content: c }: P<'quote_panel'>) {
  if (!c.quote) return null;
  return (
    <section aria-labelledby={c.label ? `${id}-t` : undefined} className="px-6 py-14">
      <div className="mx-auto max-w-7xl rounded-3xl bg-immo-dark p-10 text-immo-satin dark:border dark:border-white/15 dark:bg-black/30 lg:p-14">
        {c.label && <h2 id={`${id}-t`} className="text-sm font-semibold uppercase tracking-widest text-immo-sky">{c.label}</h2>}
        <p className="mt-4 max-w-3xl text-2xl font-semibold leading-snug text-white sm:text-3xl">{c.quote}</p>
        {c.text && <p className="mt-6 max-w-2xl text-immo-satin/90">{c.text}</p>}
      </div>
    </section>
  );
}

const TEAM_COLUMNS = { '2': 'sm:grid-cols-2', '3': 'sm:grid-cols-2 lg:grid-cols-3', '4': 'sm:grid-cols-2 lg:grid-cols-4' } as const;

/** Présentation de l'équipe : photo (médiathèque), nom, fonction, présentation courte, lien facultatif. */
export function TeamMembersBlock({ id, content: c, settings: s }: P<'team_members'>) {
  const members = (c.members ?? []).filter((m) => m.name);
  if (members.length === 0) return null;
  const inv = isInverted(s), t = tone(inv);
  const shape = s?.photo_shape === 'square' ? 'rounded-3xl' : 'rounded-full';
  const Name = c.title ? 'h3' : 'h2';
  return (
    <BlockSection look={s} labelledBy={c.title ? `${id}-t` : undefined}>
      {c.title && <h2 id={`${id}-t`} className={`max-w-3xl ${H2} ${t.title}`}>{c.title}</h2>}
      {c.intro && <p className={`mt-5 max-w-2xl text-lg ${t.body}`}>{c.intro}</p>}
      <ul className={`${c.title || c.intro ? 'mt-12' : ''} grid gap-10 ${TEAM_COLUMNS[s?.columns ?? '4']}`}>
        {members.map((m, i) => (
          <li key={i} className="flex flex-col">
            {m.photo
              ? <CmsImage image={m.photo} sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw" className={`aspect-square w-full max-w-[16rem] object-cover ${shape}`} />
              : <span aria-hidden className={`grid aspect-square w-full max-w-[16rem] place-items-center text-5xl font-semibold ${shape} ${inv ? 'bg-white/10 text-white' : 'bg-immo-sky text-immo-dark dark:bg-white/10 dark:text-immo-satin'}`}>{initials(m.name ?? '')}</span>}
            <Name className={`mt-5 text-xl font-semibold ${t.title}`}>{m.name}</Name>
            {m.role && <p className={`mt-1 font-medium ${inv ? 'text-immo-sky' : 'text-immo-dark/70 dark:text-immo-sky'}`}>{m.role}</p>}
            {m.bio && <p className={`mt-3 ${t.body}`}>{m.bio}</p>}
            {m.link && <Link href={m.link} className={`mt-4 inline-flex w-fit items-center gap-1 ${linkClass} ${inv ? 'text-white' : ''}`} {...(m.link.startsWith('http') && { target: '_blank', rel: 'noopener noreferrer' })}>{m.link.includes('linkedin') ? 'LinkedIn' : 'Profil'}<ArrowUpRight size={16} aria-hidden /></Link>}
          </li>
        ))}
      </ul>
    </BlockSection>
  );
}
