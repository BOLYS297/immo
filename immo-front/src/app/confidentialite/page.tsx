import type { Metadata } from 'next';
import LegalPage from '@/components/legal/LegalPage';
import { LEGAL } from '@/content/legal';
import { getLocale } from '@/lib/locale';
import { loadSite } from '@/server/site';

export function generateMetadata(): Metadata {
  return { title: LEGAL[getLocale()].privacy.metaTitle };
}

export default async function Page() {
  const locale = getLocale();
  const { contact } = await loadSite(locale);
  return <LegalPage doc={LEGAL[locale].privacy} locale={locale} updatedLabel={locale === 'fr' ? 'Mise à jour le' : 'Last updated'} contact={contact} />;
}
