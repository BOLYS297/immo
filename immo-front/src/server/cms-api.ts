import type { CmsPage, MediaImage } from '@/lib/blocks';
import type { Locale } from '@/lib/i18n';

/**
 * Lecture du CMS Laravel (immo-back, routes/api.php). Appels côté serveur uniquement.
 * Cache Next par tag « page:<slug> », vidé par Laravel via /api/revalidate dès qu'un contenu change ;
 * l'expiration (5 min) n'est qu'un filet si la revalidation n'arrive pas.
 * Sans CMS_API_URL, ou si l'API est injoignable, renvoie null : la page s'affiche avec ses textes par défaut.
 */
const BASE = process.env.CMS_API_URL?.replace(/\/+$/, '');

export const pageTag = (slug: string) => `page:${slug}`;

/** Administration Laravel (même hôte que l'API). */
export const cmsAdminUrl = () => (BASE ? `${new URL(BASE).origin}/admin` : null);

/** IP du visiteur, transmise à Laravel pour limiter les envois par personne et non par serveur Next. */
export const clientIp = (req: Request) => req.headers.get('x-forwarded-for')?.split(',')[0]?.trim() || req.headers.get('x-real-ip') || '';

/**
 * Appel authentifié par CMS_API_KEY : formulaires du site (devis, rendez-vous) et portail client
 * (`token` = jeton Sanctum du client connecté). status 0 = Laravel injoignable ou non configuré.
 */
export async function cmsForm<T = unknown>(method: 'GET' | 'POST' | 'PUT', path: string, init: { body?: object; ip?: string; token?: string } = {}): Promise<{ status: number; data: T | null }> {
  if (!BASE || !process.env.CMS_API_KEY) { console.error('[cms] CMS_API_URL ou CMS_API_KEY manquant : appel non transmis.'); return { status: 0, data: null }; }
  try {
    const res = await fetch(`${BASE}${path}`, {
      method,
      headers: {
        Accept: 'application/json', 'Content-Type': 'application/json', 'X-Front-Key': process.env.CMS_API_KEY, 'X-Client-Ip': init.ip ?? '',
        ...(init.token && { Authorization: `Bearer ${init.token}` }),
      },
      body: init.body ? JSON.stringify(init.body) : undefined,
      cache: 'no-store',
      signal: AbortSignal.timeout(8000),
    });
    return { status: res.status, data: (await res.json().catch(() => null)) as T | null };
  } catch (e) {
    console.error(`[cms] ${method} ${path} en échec :`, e instanceof Error ? e.message : e);
    return { status: 0, data: null };
  }
}

/** Erreur levée quand Laravel ne répond pas : la page d'erreur du site (« Réessayer ») s'affiche. */
export class CmsUnavailable extends Error {}

/** Lecture publique en cache (tags vidés par Laravel). null = 404 ; Laravel injoignable = CmsUnavailable. */
async function cmsGet<T>(path: string, tags: string[]): Promise<T | null> {
  if (!BASE) throw new CmsUnavailable('CMS_API_URL non défini');
  let res: Response;
  try {
    res = await fetch(`${BASE}${path}`, { headers: { Accept: 'application/json' }, next: { tags, revalidate: 300 }, signal: AbortSignal.timeout(4000) });
  } catch (e) {
    throw new CmsUnavailable(`${path} : ${e instanceof Error ? e.message : String(e)}`);
  }
  if (res.status === 404) return null;
  if (!res.ok) throw new CmsUnavailable(`${path} : HTTP ${res.status}`);
  return ((await res.json()) as { data?: T }).data ?? null;
}

/** Page composée de sections. Erreur ou absence = null : l'accueil retombe sur ses textes par défaut. */
export async function getPage(slug: string, locale: Locale): Promise<CmsPage | null> {
  try {
    const data = await cmsGet<CmsPage>(`/pages/${encodeURIComponent(slug)}?locale=${locale}`, [pageTag(slug)]);
    return data && Array.isArray(data.sections) ? data : null;
  } catch (e) {
    console.error(`[cms] page « ${slug} » indisponible :`, e instanceof Error ? e.message : e);
    return null;
  }
}

/** Contrat de immo-back/app/Http/Controllers/Api/SiteController.php (clés : config('cms.settings')). */
export type MenuLink = { label: string; url: string; newTab: boolean; display: 'all' | 'desktop' | 'mobile' };
export type SiteData = {
  settings: Record<
    'site_name' | 'site_slogan' | 'contact_email' | 'contact_phones' | 'contact_whatsapp' | 'contact_address'
    | 'social_linkedin' | 'social_facebook' | 'social_instagram' | 'social_x' | 'social_youtube'
    | 'header_cta_label' | 'header_cta_url' | 'seo_title' | 'seo_description' | 'logo_animation',
    string | null
  >;
  menus: { header: MenuLink[]; footer: { label: string; links: MenuLink[] }[]; legal: MenuLink[] };
};

/** Réglages et menus du site (tag « site »). null si Laravel est injoignable : le site garde ses textes par défaut. */
export async function getSite(locale: Locale): Promise<SiteData | null> {
  try {
    const data = await cmsGet<SiteData>(`/site?locale=${locale}`, ['site']);
    return data?.settings && data.menus ? data : null;
  } catch (e) {
    console.error('[cms] réglages du site indisponibles :', e instanceof Error ? e.message : e);
    return null;
  }
}

export const getPageSlugs = async () => (await cmsGet<{ slug: string; updated_at: string | null; noindex?: boolean }[]>('/pages', ['pages'])) ?? [];

/** Contrat de immo-back/app/Http/Controllers/Api/PostController.php. */
export type PostSummary = {
  slug: string; locale: Locale; date: string; updatedAt: string | null; readingMinutes: number;
  title: string; excerpt: string; tags: string[]; metaTitle: string | null; metaDescription: string | null;
};
export type Post = PostSummary & { body: string };

export const getPosts = async (locale: Locale) => (await cmsGet<PostSummary[]>(`/posts?locale=${locale}`, ['posts'])) ?? [];
export const getPost = (slug: string, locale: Locale) => cmsGet<Post>(`/posts/${encodeURIComponent(slug)}?locale=${locale}`, ['posts']);

/** Contrat de immo-back/app/Http/Controllers/Api/TestimonialController.php. */
export type TestimonialView = { id: string; name: string; role: string; company: string; pole: string; quote: string; metric: string; photo: MediaImage | null };

export const getTestimonials = async (locale: Locale) => (await cmsGet<TestimonialView[]>(`/testimonials?locale=${locale}`, ['testimonials'])) ?? [];

/** Contenus structurés (immo-back Api/StructuredContentController.php). */
export type ServiceView = { slug: string; title: string | null; description: string | null; deliverables: string[]; cta: string | null; domain: 'ia' | 'web' | 'infra' | null; icon: string };
export type ProjectView = {
  slug: string; title: string | null; client: string | null; description: string | null; problem: string | null; solution: string | null; results: string | null;
  technologies: string[]; image: MediaImage | null; url: string | null; date: string | null; featured: boolean;
};
export type FaqView = { id: number; question: string | null; answer: string | null; category: string | null };

export const getServices = async (locale: Locale) => (await cmsGet<ServiceView[]>(`/services?locale=${locale}`, ['services'])) ?? [];
export const getProjects = async (locale: Locale) => (await cmsGet<ProjectView[]>(`/projects?locale=${locale}`, ['projects'])) ?? [];
export const getFaqs = async (locale: Locale) => (await cmsGet<FaqView[]>(`/faqs?locale=${locale}`, ['faqs'])) ?? [];
