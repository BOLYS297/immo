import Link from 'next/link';
import type { ComponentPropsWithoutRef } from 'react';

/** Lien saisi dans l'admin : chemin interne (navigation Next) ou adresse externe. */
export default function SiteLink({ url, newTab, ...props }: { url: string; newTab?: boolean } & Omit<ComponentPropsWithoutRef<'a'>, 'href'>) {
  const target = newTab ? { target: '_blank', rel: 'noopener noreferrer' } : {};
  return url.startsWith('/') ? <Link href={url} {...target} {...props} /> : <a href={url} {...target} rel="noopener noreferrer" {...props} />;
}
