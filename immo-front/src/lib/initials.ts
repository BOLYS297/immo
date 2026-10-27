/** Initiales d'un nom (deux premiers mots) : remplace une photo absente (avis, équipe). */
export const initials = (name: string) =>
  name.trim().split(/\s+/).filter(Boolean).slice(0, 2).map((word) => word[0]!.toUpperCase()).join('');
