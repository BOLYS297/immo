import type { Section } from '@/lib/blocks';
import type { Dict } from '@/lib/dictionaries/fr';

/**
 * Pages « système » du site, composées de sections dans le CMS (admin « Pages & Sections ») :
 * elles ne peuvent être ni supprimées ni renommées (immo-back config/cms.php, system_pages).
 * Composition ci-dessous = textes des dictionnaires : affichée si Laravel est injoignable, et
 * reprise telle quelle par le seeder Laravel (database/seeders/data/system-pages.json, généré).
 * Ce fichier ne doit importer que des types : il est aussi exécuté par Node pour générer ce JSON.
 */
export const SYSTEM_SLUGS = ['services', 'a-propos', 'recommandations', 'contact', 'tech-insights'] as const;
export type SystemSlug = (typeof SYSTEM_SLUGS)[number];

export type SystemPage = { title: string; meta: { title: string; description: string }; sections: Section[] };

const DOMAINS = ['ia', 'web', 'infra'] as const;

export function systemPage(slug: SystemSlug, t: Dict): SystemPage {
  const p = t.pages;
  let i = 0;
  // Identifiants stables, propres à la composition de repli (les sections du CMS ont leur id numérique).
  const section = <S extends Section>(s: Omit<S, 'id'>) => ({ ...s, id: `${slug}-${i++}` }) as S;

  switch (slug) {
    case 'services': {
      const s = p.services;
      return {
        title: t.nav.solutions,
        meta: { title: s.metaTitle, description: s.metaDescription },
        sections: [
          section({ type: 'page_header', content: { title: s.title, intro: s.intro }, settings: { size: 'xl' } }),
          // Domaines : services publiés dans l'admin ; ceux saisis ici servent de repli (Laravel injoignable, aucun service).
          section({ type: 'service_domains', content: {
            title: s.domainsTitle, deliverables_label: s.deliverables,
            domains: s.domains.map((d, k) => ({ title: d.title, benefit: d.benefit, deliverables: d.deliverables.join('\n'), cta: d.cta, href: `/services?domaine=${DOMAINS[k]}#chiffrage` })),
          }, settings: { source: 'services' } }),
          section({ type: 'use_cases', content: { title: s.casesTitle, situation_label: s.situation, solution_label: s.solution, result_label: s.result, cases: s.cases } }),
          section({ type: 'methodology', content: { title: s.methodTitle, step_label: p.common.step, steps: s.steps } }),
          section({ type: 'quote_form', content: { title: s.quoteTitle, intro: s.quoteIntro }, settings: { background: 'satin', spacing: 'normal', default_domain: 'ia' } }),
        ],
      };
    }
    case 'a-propos': {
      const a = p.about;
      return {
        title: t.nav.about,
        meta: { title: a.metaTitle, description: a.metaDescription },
        sections: [
          section({ type: 'about_intro', content: { title: a.title, intro: a.intro, items: a.meaning } }),
          section({ type: 'quote_panel', content: { label: a.philoLabel, quote: a.philoQuote, text: a.philoText } }),
          section({ type: 'team', content: { title: a.storyTitle, body: `${a.story1}\n\n${a.story2}`, members: a.founders.map((f) => ({ title: f.pole, text: f.text })) } }),
          section({ type: 'commitments', content: { title: a.commitTitle, intro: a.commitIntro, items: t.pillars.items, button: a.meet, href: '/contact' } }),
        ],
      };
    }
    case 'recommandations': {
      const r = p.reco;
      return {
        title: t.nav.reco,
        meta: { title: r.metaTitle, description: r.metaDescription },
        sections: [
          section({ type: 'page_header', content: { title: r.title }, settings: { size: 'lg' } }),
          section({ type: 'stats', content: { items: r.metrics }, settings: { background: 'satin', spacing: 'compact' } }),
          section({ type: 'testimonials_wall', content: { title: r.wallTitle, empty_text: r.empty }, settings: { background: 'satin', spacing: 'compact' } }),
          section({ type: 'callout', content: { title: r.authTitle, text: r.authText, link_label: r.authLink, href: '/contact' }, settings: { spacing: 'compact' } }),
        ],
      };
    }
    case 'contact': {
      const c = p.contact;
      return {
        title: t.nav.contact,
        meta: { title: c.metaTitle, description: c.metaDescription },
        sections: [
          section({ type: 'booking', content: { title: c.title, intro: c.intro, aside_title: c.asideTitle }, settings: { background: 'satin', spacing: 'normal', layout: 'page' } }),
        ],
      };
    }
    case 'tech-insights': {
      const n = p.insights;
      return {
        title: t.footer.insights,
        meta: { title: n.metaTitle, description: n.metaDescription },
        sections: [
          section({ type: 'page_header', content: { title: n.title, intro: n.intro }, settings: { size: 'lg' } }),
          section({ type: 'posts_list', content: {}, settings: { background: 'satin', spacing: 'compact', count: 'all' } }),
        ],
      };
    }
  }
}
