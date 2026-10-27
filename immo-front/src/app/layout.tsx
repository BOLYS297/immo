import type { Metadata, Viewport } from 'next';
import '@fontsource-variable/outfit';
import './globals.css';
import Navbar from '@/components/layout/Navbar';
import Footer from '@/components/layout/Footer';
import ScrollProgress from '@/components/ui/ScrollProgress';
import { I18nProvider } from '@/components/I18nProvider';
import { getLocale } from '@/lib/locale';
import { dictionaries } from '@/lib/dictionaries';
import { HAS_GILROY } from '@/lib/fonts';
import ServiceWorker from '@/components/ServiceWorker';
import { DEFAULT_OG_IMAGE, jsonLd, SITE_URL } from '@/lib/seo';
import { loadSite } from '@/server/site';

export async function generateMetadata(): Promise<Metadata> {
  const locale = getLocale();
  const t = dictionaries[locale];
  // Titre et description par défaut : admin « Réglages du site » ; chaque page peut les remplacer.
  const { name, seo } = await loadSite(locale);
  return {
    metadataBase: new URL(process.env.NEXT_PUBLIC_SITE_URL ?? 'http://localhost:3000'),
    title: seo.title,
    description: seo.description,
    openGraph: { title: seo.title, description: seo.description, siteName: name, locale: t.meta.ogLocale, type: 'website', images: [DEFAULT_OG_IMAGE] },
    twitter: { card: 'summary_large_image', images: [DEFAULT_OG_IMAGE] },
    icons: { apple: '/icons/apple-touch-icon.png' },
    appleWebApp: { capable: true, title: 'IMMO', statusBarStyle: 'black-translucent' },
  };
}

export const viewport: Viewport = {
  themeColor: [
    { media: '(prefers-color-scheme: light)', color: '#F4F4F4' },
    { media: '(prefers-color-scheme: dark)', color: '#273480' },
  ],
};

// Light par défaut ; applique le choix mémorisé avant le premier rendu (pas de flash).
const themeScript = `try{if(localStorage.getItem('immo-theme')==='dark'){document.documentElement.classList.add('dark')}}catch(e){}`;

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  const locale = getLocale();
  // Données structurées de l'organisation (moteurs de recherche) : réglages du site.
  const site = await loadSite(locale);
  const organization = {
    '@context': 'https://schema.org', '@type': 'Organization', name: site.name, url: SITE_URL, logo: `${SITE_URL}/icons/icon-512.png`,
    ...(site.contact.email && { email: site.contact.email }),
    ...(site.contact.phones[0] && { telephone: site.contact.phones[0].href }),
    ...(site.contact.address.length > 0 && { address: { '@type': 'PostalAddress', streetAddress: site.contact.address.join(', ') } }),
    ...(site.social.length > 0 && { sameAs: site.social.map((s) => s.url) }),
  };
  return (
    <html lang={locale} suppressHydrationWarning>
      <head>
        <script dangerouslySetInnerHTML={{ __html: themeScript }} />
        {HAS_GILROY && (
          <>
            <link rel="preload" href="/fonts/Gilroy-Regular.woff2" as="font" type="font/woff2" crossOrigin="anonymous" />
            <link rel="preload" href="/fonts/Gilroy-SemiBold.woff2" as="font" type="font/woff2" crossOrigin="anonymous" />
            <link rel="stylesheet" href="/fonts/gilroy.css" />
          </>
        )}
      </head>
      <body>
        <script type="application/ld+json" dangerouslySetInnerHTML={jsonLd(organization)} />
        <I18nProvider locale={locale} dict={dictionaries[locale]}>
          <ScrollProgress />
          <Navbar />
          {children}
          <Footer />
          <ServiceWorker />
        </I18nProvider>
      </body>
    </html>
  );
}
