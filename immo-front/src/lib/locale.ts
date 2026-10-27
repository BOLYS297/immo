import { cookies, headers } from 'next/headers';
import { COOKIE, DEFAULT_LOCALE, isLocale, type Locale } from './i18n';
import { dictionaries } from './dictionaries';

export function getLocale(): Locale {
  const c = cookies().get(COOKIE)?.value;
  if (isLocale(c)) return c;
  const h = headers().get('x-immo-locale');
  return isLocale(h) ? h : DEFAULT_LOCALE;
}
export const getDict = () => dictionaries[getLocale()];
