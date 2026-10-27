/**
 * Contrat des sections renvoyées par le CMS Laravel (immo-back : config/cms.php et PageResource).
 * Chaque champ est facultatif : un champ absent reprend le texte du dictionnaire de la langue (blocs de l'accueil)
 * ou n'est pas affiché (blocs génériques). Les champs « richtext » suivent la syntaxe de src/components/RichText.tsx.
 */
export type Link = { title: string; text: string; href: string };

/** Image de la médiathèque Laravel (champ « image »), déjà résolue par l'API. */
export type MediaImage = { id: number; url: string; width: number; height: number; alt: string };

export type BlockContentMap = {
  // Blocs génériques
  text: { eyebrow: string; title: string; body: string };
  media_text: { image: MediaImage; eyebrow: string; title: string; body: string; link_label: string; href: string };
  cards: { title: string; intro: string; items: { image?: MediaImage; title?: string; text?: string; link_label?: string; href?: string }[] };
  stats: { title: string; items: { value?: string; label?: string }[] };
  faq: { title: string; items: { question?: string; answer?: string }[] };
  cta_band: { title: string; text: string; primary: string; primary_href: string; secondary: string; secondary_href: string };
  page_header: { eyebrow: string; title: string; intro: string };
  callout: { title: string; text: string; link_label: string; href: string };
  quote_panel: { label: string; quote: string; text: string };
  // Blocs dynamiques et fonctionnels
  posts_list: { title: string; intro: string; link_label: string };
  testimonials_wall: { title: string; intro: string; empty_text: string };
  projects_grid: { title: string; intro: string; results_label: string };
  faq_list: { title: string; category: string };
  team_members: { title: string; intro: string; members: { photo?: MediaImage; name?: string; role?: string; bio?: string; link?: string }[] };
  quote_form: { title: string; intro: string };
  booking: { title: string; intro: string; aside_title: string };
  contact_form: { title: string; intro: string };
  // Blocs de l'accueil
  hero: { eyebrow: string; title: string; text: string };
  statement: { text: string };
  services: { title: string; all: string; rows: Link[] };
  pillars: { title: string; items: { title: string; text: string }[] };
  diagnostic: { title: string; text: string };
  terminal: { title: string; text: string };
  portal_preview: { title: string; text: string; button: string };
  // Blocs des pages du site (textes par défaut : dictionnaires)
  service_domains: { title: string; deliverables_label: string; domains: { title?: string; benefit?: string; deliverables?: string; cta?: string; href?: string }[] };
  use_cases: { title: string; situation_label: string; solution_label: string; result_label: string; cases: { title?: string; situation?: string; solution?: string; result?: string }[] };
  methodology: { title: string; step_label: string; steps: { title?: string; text?: string }[] };
  about_intro: { title: string; intro: string; items: { title?: string; text?: string }[] };
  team: { title: string; body: string; members: { title?: string; text?: string }[] };
  commitments: { title: string; intro: string; items: { title?: string; text?: string }[]; button: string; href: string };
};

/** Réglages de présentation (communs aux langues), toujours renseignés par Laravel avec leurs valeurs par défaut. */
export type Background = 'satin' | 'white' | 'sky' | 'dark';
/** Marges verticales d'un bloc générique : « compact » pour enchaîner des blocs comme sur une page de contenu. */
export type Spacing = 'normal' | 'compact';
/** Voile posé sur l'image de fond d'une section : « dark » = texte blanc, « light » et « none » = texte Bleu Nuit. */
export type Overlay = 'dark' | 'light' | 'none';
/** Présentation commune des sections : fond, marges, image de fond (médiathèque) et son voile. */
export type Look = { background: Background; spacing: Spacing; background_image: MediaImage | null; overlay: Overlay };
export type BlockSettingsMap = {
  text: Look & { align: 'left' | 'center' };
  media_text: Look & { image_position: 'left' | 'right' };
  cards: Look & { columns: '2' | '3' | '4' };
  stats: Look;
  faq: Look;
  posts_list: Look & { count: '3' | '6' | '9' | 'all' };
  testimonials_wall: Look;
  quote_form: Look & { default_domain: 'ia' | 'web' | 'infra' };
  booking: Look & { layout: 'section' | 'page' };
  contact_form: Look;
  page_header: { size: 'xl' | 'lg' };
  service_domains: { source: 'manual' | 'services' };
  projects_grid: Look & { count: '3' | '6' | 'all'; selection: 'all' | 'featured' };
  faq_list: Look;
  team_members: Look & { columns: '2' | '3' | '4'; photo_shape: 'round' | 'square' };
  callout: { spacing: Spacing };
};

export type BlockType = keyof BlockContentMap;
export type BlockContent<K extends BlockType> = Partial<BlockContentMap[K]>;
export type BlockSettings<K extends BlockType> = K extends keyof BlockSettingsMap ? Partial<BlockSettingsMap[K]> : Record<string, never>;

export type Section = { [K in BlockType]: { id: number | string; type: K; content: BlockContent<K>; settings?: BlockSettings<K> } }[BlockType];

export type CmsPage = {
  slug: string;
  locale: string;
  title: string | null;
  meta: { title: string | null; description: string | null; image?: MediaImage | null; noindex?: boolean };
  sections: Section[];
  updated_at: string | null;
};
