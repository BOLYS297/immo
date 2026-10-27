import type { Locale } from '../i18n';
import { fr, type Dict } from './fr';
import { en } from './en';

export type { Dict };
export const dictionaries: Record<Locale, Dict> = { fr, en };
