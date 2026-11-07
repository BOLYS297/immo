import { ArrowUpRight, Dribbble, Facebook, Github, Globe, Instagram, Linkedin, MessageCircle, Twitter, Youtube, type LucideIcon } from 'lucide-react';
import SiteLink from '@/components/layout/SiteLink';
import type { Button, Social } from '@/lib/blocks';

/**
 * Boutons et liens saisis dans l'admin (champs « buttons » et « socials » de config/cms.php).
 * Un bouton sans lien est ignoré ; `legacy` reprend l'ancien bouton unique d'un contenu pas encore converti.
 */
export function buttonsOf(list: Button[] | undefined, legacy?: Button): { label: string; href: string }[] {
  const source = list?.length ? list : legacy ? [legacy] : [];
  return source.filter((b) => b.href).map((b) => ({ label: b.label ?? '', href: b.href as string }));
}

const external = (href: string) => /^https?:\/\//i.test(href);

/** Rangée de boutons : le premier est mis en avant (cyan), les suivants sont secondaires (contour). */
export function ButtonRow({ buttons, inverted = false, className = 'mt-8' }: { buttons: { label: string; href: string }[]; inverted?: boolean; className?: string }) {
  const shown = buttons.filter((b) => b.label);
  if (shown.length === 0) return null;
  return (
    <div className={`flex flex-wrap gap-3 ${className}`}>
      {shown.map((b, i) => (
        <SiteLink key={i} url={b.href} newTab={external(b.href)}
          className={i === 0
            ? 'rounded-full bg-immo-cyan px-7 py-3.5 text-center font-semibold text-immo-dark transition-colors hover:bg-immo-sky'
            : `rounded-full border px-7 py-3.5 text-center font-semibold transition-colors ${inverted ? 'border-white/40 text-white hover:bg-white/10' : 'border-immo-dark/30 text-immo-dark hover:bg-immo-sky/50 dark:border-white/30 dark:text-white dark:hover:bg-white/10'}`}>
          {b.label}
        </SiteLink>
      ))}
    </div>
  );
}

/** Liens soulignés (cartes, encadrés) ; `fallback` remplace un libellé vide (ex. titre de la carte). */
export function LinkList({ buttons, fallback = '', inverted = false, className = 'mt-5' }: { buttons: { label: string; href: string }[]; fallback?: string; inverted?: boolean; className?: string }) {
  const shown = buttons.filter((b) => b.label || fallback);
  if (shown.length === 0) return null;
  return (
    <div className={`flex flex-wrap gap-x-6 gap-y-2 ${className}`}>
      {shown.map((b, i) => (
        <SiteLink key={i} url={b.href} newTab={external(b.href)} className={`inline-flex items-center gap-1.5 font-semibold underline decoration-immo-cyan decoration-2 underline-offset-4 ${inverted ? 'text-white' : ''}`}>
          {b.label || fallback} <ArrowUpRight size={16} aria-hidden />
        </SiteLink>
      ))}
    </div>
  );
}

/** Réseaux proposés dans l'admin (immo-back config/cms.php, social_networks). */
const NETWORKS: Record<string, { name: string; icon: LucideIcon | 'tiktok' }> = {
  linkedin: { name: 'LinkedIn', icon: Linkedin }, x: { name: 'X', icon: Twitter }, facebook: { name: 'Facebook', icon: Facebook },
  instagram: { name: 'Instagram', icon: Instagram }, tiktok: { name: 'TikTok', icon: 'tiktok' }, youtube: { name: 'YouTube', icon: Youtube },
  github: { name: 'GitHub', icon: Github }, dribbble: { name: 'Dribbble', icon: Dribbble }, whatsapp: { name: 'WhatsApp', icon: MessageCircle },
  website: { name: 'Site', icon: Globe },
};

function TikTokIcon({ size }: { size: number }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="currentColor" aria-hidden>
      <path d="M12.53.02C13.84 0 15.14.01 16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z" />
    </svg>
  );
}

/** Icônes des réseaux d'une personne ; le nom du réseau et de la personne sont lus par les lecteurs d'écran. */
export function SocialLinks({ socials, person, inverted = false, className = 'mt-4' }: { socials: Social[] | undefined; person: string; inverted?: boolean; className?: string }) {
  const links = (socials ?? []).filter((s) => s.url);
  if (links.length === 0) return null;
  return (
    <ul className={`flex flex-wrap gap-2 ${className}`}>
      {links.map((s, i) => {
        const network = NETWORKS[s.network ?? ''] ?? NETWORKS.website;
        const Icon = network.icon;
        return (
          <li key={i}>
            <SiteLink url={s.url as string} newTab={external(s.url as string)} aria-label={`${network.name} — ${person}`} title={network.name}
              className={`grid size-10 place-items-center rounded-full border transition-colors ${inverted ? 'border-white/30 text-white hover:bg-white/10' : 'border-immo-dark/20 text-immo-dark hover:border-immo-cyan hover:bg-immo-sky/50 dark:border-white/25 dark:text-white dark:hover:bg-white/10'}`}>
              {Icon === 'tiktok' ? <TikTokIcon size={18} /> : <Icon size={18} aria-hidden />}
            </SiteLink>
          </li>
        );
      })}
    </ul>
  );
}
