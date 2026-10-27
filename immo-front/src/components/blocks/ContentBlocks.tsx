import Image from 'next/image';
import Link from 'next/link';
import { ArrowUpRight } from 'lucide-react';
import Funnel from '@/components/contact/Funnel';
import ContactList from '@/components/layout/ContactList';
import MessageForm from '@/components/contact/MessageForm';
import PostCard from '@/components/insights/PostCard';
import TestimonialWall from '@/components/reco/TestimonialWall';
import QuoteForm from '@/components/services/QuoteForm';
import type { BlockContent, BlockSettings } from '@/lib/blocks';
import { getDict, getLocale } from '@/lib/locale';
import type { DomainKey } from '@/lib/services-data';
import { getFaqs, getPosts, getProjects, getTestimonials } from '@/server/cms-api';
import { loadSite } from '@/server/site';
import BlockSection, { isInverted, tone } from './BlockSection';
import { FaqBlock } from './GenericBlocks';

/**
 * Blocs dynamiques (contenus gérés ailleurs dans l'admin) et fonctionnels (formulaires du site).
 * Un bloc dynamique sans contenu, ou dont la source est indisponible, ne s'affiche pas : le reste de la page reste visible.
 */
type P<K extends 'posts_list' | 'testimonials_wall' | 'quote_form' | 'booking' | 'projects_grid' | 'faq_list' | 'contact_form'> = { id: string; content: BlockContent<K>; settings?: BlockSettings<K> };

const H2 = 'text-4xl font-semibold tracking-tighter sm:text-5xl';

function Heading({ id, title, intro, inverted }: { id: string; title?: string; intro?: string; inverted: boolean }) {
  const t = tone(inverted);
  return (
    <>
      {title && <h2 id={`${id}-t`} className={`max-w-3xl ${H2} ${t.title}`}>{title}</h2>}
      {intro && <p className={`mt-5 max-w-2xl text-lg ${t.body}`}>{intro}</p>}
    </>
  );
}

export async function PostsListBlock({ id, content: c, settings: s }: P<'posts_list'>) {
  const locale = getLocale();
  const all = await getPosts(locale).catch(() => []);
  const posts = s?.count === 'all' ? all : all.slice(0, Number(s?.count ?? 3));
  if (posts.length === 0) return null;
  const inv = isInverted(s);
  return (
    <BlockSection look={s} labelledBy={c.title ? `${id}-t` : undefined}>
      <div className="flex flex-wrap items-end justify-between gap-6">
        <div><Heading id={id} title={c.title} intro={c.intro} inverted={inv} /></div>
        {c.link_label && <Link href="/tech-insights" className={`font-semibold underline decoration-immo-cyan decoration-2 underline-offset-4 ${inv ? 'text-white' : ''}`}>{c.link_label}</Link>}
      </div>
      <ul className="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {posts.map((p) => <PostCard key={p.slug} post={p} locale={locale} readMinutes={getDict().pages.common.readMinutes} heading={c.title ? 'h3' : 'h2'} />)}
      </ul>
    </BlockSection>
  );
}

export async function TestimonialsBlock({ id, content: c, settings: s }: P<'testimonials_wall'>) {
  const items = await getTestimonials(getLocale()).catch(() => []);
  // Aucun avis publié : message s'il est renseigné (page Recommandations), sinon pas de mur vide.
  if (items.length === 0 && !c.empty_text) return null;
  return (
    <BlockSection look={s} labelledBy={c.title ? `${id}-t` : undefined}>
      <Heading id={id} title={c.title} intro={c.intro} inverted={isInverted(s)} />
      {items.length > 0 ? <TestimonialWall items={items} /> : <p className="mt-8 rounded-2xl border border-immo-dark/15 p-7 dark:border-white/15">{c.empty_text}</p>}
    </BlockSection>
  );
}

/** Ancre #chiffrage et ?domaine=… conservés : les liens « /page?domaine=ia#chiffrage » ouvrent le bon pôle. */
export function QuoteFormBlock({ id, content: c, settings: s, domain }: P<'quote_form'> & { domain?: string }) {
  const fromUrl: DomainKey | undefined = domain === 'ia' || domain === 'web' || domain === 'infra' ? domain : undefined;
  return (
    <div id="chiffrage" className="scroll-mt-24">
      <BlockSection look={s} labelledBy={c.title ? `${id}-t` : undefined}>
        <div className="mx-auto max-w-3xl">
          <Heading id={id} title={c.title} intro={c.intro} inverted={isInverted(s)} />
          <div className={c.title || c.intro ? 'mt-8' : ''}><QuoteForm defaultDomain={fromUrl ?? s?.default_domain} /></div>
        </div>
      </BlockSection>
    </div>
  );
}

/** Mise en page « page » : titre principal (h1) et coordonnées du site à droite, comme la page Contact. */
export async function BookingBlock({ id, content: c, settings: s }: P<'booking'>) {
  if (s?.layout === 'page') {
    const t = getDict();
    const { contact } = await loadSite(getLocale());
    return (
      <section aria-labelledby={c.title ? `${id}-t` : undefined} className="px-6 py-16 lg:py-24">
        <div className="mx-auto grid max-w-7xl gap-14 lg:grid-cols-[1.4fr_0.6fr]">
          <div>
            {c.title && <h1 id={`${id}-t`} className="max-w-2xl text-5xl font-semibold leading-[1.04] tracking-tighter text-immo-dark sm:text-6xl dark:text-white">{c.title}</h1>}
            {c.intro && <p className="mt-5 max-w-xl text-lg text-immo-dark/85 dark:text-immo-satin/90">{c.intro}</p>}
            <div className={c.title || c.intro ? 'mt-10' : ''}><Funnel /></div>
          </div>
          <aside aria-label={c.aside_title || t.footer.contact} className="h-fit border-t-2 border-immo-dark pt-6 dark:border-immo-cyan">
            {c.aside_title && <h2 className="text-xl font-semibold text-immo-dark dark:text-white">{c.aside_title}</h2>}
            <ContactList contact={contact} whatsappLabel={t.footer.whatsapp} linkClass="hover:underline" className="mt-5 space-y-4" />
          </aside>
        </div>
      </section>
    );
  }
  return (
    <BlockSection look={s} labelledBy={c.title ? `${id}-t` : undefined}>
      <div className="max-w-4xl">
        <Heading id={id} title={c.title} intro={c.intro} inverted={isInverted(s)} />
        <div className={c.title || c.intro ? 'mt-10' : ''}><Funnel /></div>
      </div>
    </BlockSection>
  );
}

/** Réalisations publiées (rubrique « Réalisations » ; seulement celles dont le client a donné son accord écrit). */
export async function ProjectsBlock({ id, content: c, settings: s }: P<'projects_grid'>) {
  const all = (await getProjects(getLocale()).catch(() => [])).filter((p) => s?.selection !== 'featured' || p.featured);
  const projects = s?.count === 'all' ? all : all.slice(0, Number(s?.count ?? 3));
  if (projects.length === 0) return null;
  const inv = isInverted(s), t = tone(inv);
  return (
    <BlockSection look={s} labelledBy={c.title ? `${id}-t` : undefined}>
      <Heading id={id} title={c.title} intro={c.intro} inverted={inv} />
      <ul className={`${c.title || c.intro ? 'mt-12' : ''} grid gap-6 md:grid-cols-2 lg:grid-cols-3`}>
        {projects.map((p) => {
          const Title = c.title ? 'h3' : 'h2';
          return (
            <li key={p.slug} className={`flex flex-col overflow-hidden rounded-2xl border ${t.card}`}>
              {p.image && <Image src={p.image.url} width={p.image.width} height={p.image.height} alt={p.image.alt} sizes="(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw" className="aspect-[16/10] w-full object-cover" />}
              <div className="flex flex-1 flex-col p-7">
                {p.client && <p className={`text-sm font-semibold ${inv ? 'text-immo-sky' : 'text-immo-dark/70 dark:text-immo-sky'}`}>{p.client}</p>}
                <Title className={`mt-2 text-xl font-semibold ${t.title}`}>{p.title}</Title>
                {p.description && <p className={`mt-3 ${t.body}`}>{p.description}</p>}
                {p.results && (
                  <p className={`mt-5 border-l-4 border-immo-cyan pl-4 font-semibold ${t.title}`}>
                    {c.results_label && <span className={`block text-sm font-medium ${t.muted}`}>{c.results_label}</span>}
                    {p.results}
                  </p>
                )}
                {p.technologies.length > 0 && (
                  <ul className="mt-5 flex flex-wrap gap-2" aria-label="Technologies">
                    {p.technologies.map((tech) => <li key={tech} className={`rounded-full px-3 py-1 text-sm ${inv ? 'bg-white/10 text-immo-satin' : 'bg-immo-sky/60 text-immo-dark dark:bg-white/10 dark:text-immo-satin'}`}>{tech}</li>)}
                  </ul>
                )}
                {p.url && (
                  <a href={p.url} target="_blank" rel="noopener noreferrer" className={`mt-auto inline-flex items-center gap-1 pt-6 font-semibold underline decoration-immo-cyan decoration-2 underline-offset-4 ${inv ? 'text-white' : ''}`}>
                    {new URL(p.url).hostname.replace(/^www\./, '')} <ArrowUpRight size={16} aria-hidden />
                  </a>
                )}
              </div>
            </li>
          );
        })}
      </ul>
    </BlockSection>
  );
}

/** Questions de la rubrique FAQ (toutes, ou une catégorie : nom français), au rendu du bloc « Questions fréquentes ». */
export async function FaqListBlock({ id, content: c, settings: s }: P<'faq_list'>) {
  const wanted = c.category?.trim().toLowerCase();
  const faqs = (await getFaqs(getLocale()).catch(() => [])).filter((f) => !wanted || f.category?.trim().toLowerCase() === wanted);
  return <FaqBlock id={id} content={{ title: c.title, items: faqs.map((f) => ({ question: f.question ?? undefined, answer: f.answer ?? undefined })) }} settings={s} />;
}

/** Formulaire « Écrivez-nous » : le message arrive dans l'admin (Messages) et est notifié par email. */
export function ContactFormBlock({ id, content: c, settings: s }: P<'contact_form'>) {
  return (
    <BlockSection look={s} labelledBy={c.title ? `${id}-t` : undefined}>
      <div className="max-w-3xl">
        <Heading id={id} title={c.title} intro={c.intro} inverted={isInverted(s)} />
        <div className={c.title || c.intro ? 'mt-8' : ''}><MessageForm /></div>
      </div>
    </BlockSection>
  );
}
