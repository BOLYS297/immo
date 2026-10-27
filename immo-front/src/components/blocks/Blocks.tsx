import { Fragment, type ReactNode } from 'react';
import Hero from '@/components/home/Hero';
import Statement from '@/components/home/Statement';
import Services from '@/components/home/Services';
import Pillars from '@/components/home/Pillars';
import DiagnosticSection from '@/components/home/DiagnosticSection';
import IMMOTerminal from '@/components/home/IMMOTerminal';
import PortalPreview from '@/components/home/PortalPreview';
import CtaBand from '@/components/home/CtaBand';
import Reveal from '@/components/ui/Reveal';
import { AboutIntro, Commitments, Team } from '@/components/about/AboutBlocks';
import Methodology from '@/components/services/Methodology';
import ServiceDomains from '@/components/services/ServiceDomains';
import UseCases from '@/components/services/UseCases';
import type { BlockType, Section } from '@/lib/blocks';
import { BookingBlock, ContactFormBlock, FaqListBlock, PostsListBlock, ProjectsBlock, QuoteFormBlock, TestimonialsBlock } from './ContentBlocks';
import { CalloutBlock, CardsBlock, TeamMembersBlock, FaqBlock, MediaTextBlock, PageHeaderBlock, QuotePanelBlock, StatsBlock, TextBlock } from './GenericBlocks';

/** Paramètres d'adresse utiles aux blocs (ex. ?domaine=ia pour le formulaire de chiffrage). */
export type BlockSearchParams = { domaine?: string };

/** Registre type de bloc → composant. Ajouter un type ici ET dans immo-back/config/cms.php. */
function render(s: Section, params: BlockSearchParams): ReactNode {
  const id = `s${s.id}`;
  switch (s.type) {
    case 'posts_list': return <PostsListBlock id={id} content={s.content} settings={s.settings} />;
    case 'testimonials_wall': return <TestimonialsBlock id={id} content={s.content} settings={s.settings} />;
    case 'projects_grid': return <ProjectsBlock id={id} content={s.content} settings={s.settings} />;
    case 'faq_list': return <FaqListBlock id={id} content={s.content} settings={s.settings} />;
    case 'quote_form': return <QuoteFormBlock id={id} content={s.content} settings={s.settings} domain={params.domaine} />;
    case 'booking': return <BookingBlock id={id} content={s.content} settings={s.settings} />;
    case 'contact_form': return <ContactFormBlock id={id} content={s.content} settings={s.settings} />;
    case 'text': return <TextBlock id={id} content={s.content} settings={s.settings} />;
    case 'media_text': return <MediaTextBlock id={id} content={s.content} settings={s.settings} />;
    case 'cards': return <CardsBlock id={id} content={s.content} settings={s.settings} />;
    case 'stats': return <StatsBlock id={id} content={s.content} settings={s.settings} />;
    case 'faq': return <FaqBlock id={id} content={s.content} settings={s.settings} />;
    case 'page_header': return <PageHeaderBlock id={id} content={s.content} settings={s.settings} />;
    case 'callout': return <CalloutBlock id={id} content={s.content} settings={s.settings} />;
    case 'team_members': return <TeamMembersBlock id={id} content={s.content} settings={s.settings} />;
    case 'quote_panel': return <QuotePanelBlock id={id} content={s.content} />;
    case 'service_domains': return <ServiceDomains content={s.content} settings={s.settings} />;
    case 'use_cases': return <UseCases content={s.content} />;
    case 'methodology': return <Methodology content={s.content} />;
    case 'about_intro': return <AboutIntro id={id} content={s.content} />;
    case 'team': return <Team id={id} content={s.content} />;
    case 'commitments': return <Commitments id={id} content={s.content} />;
    case 'hero': return <Hero content={s.content} />;
    case 'statement': return <Statement content={s.content} />;
    case 'services': return <Services content={s.content} />;
    case 'pillars': return <Pillars content={s.content} />;
    case 'diagnostic': return <DiagnosticSection content={s.content} />;
    case 'terminal': return <IMMOTerminal content={s.content} />;
    case 'portal_preview': return <PortalPreview content={s.content} />;
    case 'cta_band': return <CtaBand content={s.content} />;
    default: return null; // type inconnu de ce déploiement du front : ignoré
  }
}

// Sections qui apparaissent en fondu au défilement (le hero et le manifeste ont leur propre animation).
// Jamais la première section d'une page : elle est visible dès le chargement (titre principal).
const REVEAL = new Set<BlockType>(['services', 'diagnostic', 'terminal', 'portal_preview', 'cta_band', 'text', 'media_text', 'cards', 'team_members', 'stats', 'faq', 'posts_list', 'testimonials_wall', 'projects_grid', 'faq_list', 'quote_form', 'booking', 'contact_form']);

/** `anchors` (aperçu de l'admin) : ancre #section-<id> devant chaque section, pour y faire défiler l'aperçu en direct. */
export default function Blocks({ sections, searchParams = {}, anchors = false }: { sections: Section[]; searchParams?: BlockSearchParams; anchors?: boolean }) {
  return sections.map((s, i) => {
    const node = render(s, searchParams);
    if (!node) return null;
    const block = i > 0 && REVEAL.has(s.type) && !anchors ? <Reveal>{node}</Reveal> : node;
    return (
      <Fragment key={s.id}>
        {anchors && <span id={`section-${s.id}`} aria-hidden className="block scroll-mt-24" />}
        {block}
      </Fragment>
    );
  });
}
