import { icons, type LucideIcon } from 'lucide-react';

/**
 * Icône Lucide d'après son nom saisi dans l'admin (« brain-circuit » → BrainCircuit), ou undefined si elle n'existe pas.
 * À n'utiliser que côté serveur : l'ensemble des icônes n'entre pas dans le JavaScript envoyé au navigateur.
 */
export function lucideIcon(name: string | null | undefined): LucideIcon | undefined {
  if (!name || !/^[a-z0-9]+(-[a-z0-9]+)*$/.test(name)) return undefined;
  const pascal = name.split('-').map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join('');
  return (icons as Record<string, LucideIcon>)[pascal];
}
