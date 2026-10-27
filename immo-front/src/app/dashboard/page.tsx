import type { Metadata } from 'next';
import { redirect } from 'next/navigation';
import DashboardApp from '@/components/dashboard/DashboardApp';
import { getDict } from '@/lib/locale';
import type { Item, PortalUser } from '@/lib/portal-types';
import { getSession } from '@/server/auth';
import { cmsForm } from '@/server/cms-api';

export function generateMetadata(): Metadata {
  return { title: getDict().pages.dashboard.metaTitle, robots: { index: false, follow: false } };
}

/** Espace client : données lues dans Laravel avec le jeton du client (ou de l'aperçu administrateur). */
export default async function DashboardPage() {
  const s = await getSession();
  if (!s) redirect('/connexion');
  if (s.role !== 'client' || !s.token) redirect('/api/auth/logout'); // ancienne session (ex. admin du front supprimé)

  const { status, data } = await cmsForm<{ user: PortalUser; items: Item[]; preview: boolean }>('GET', '/portal/me', { token: s.token });
  if (status === 401 || status === 403) redirect('/api/auth/logout'); // jeton expiré, révoqué ou compte désactivé
  if (status !== 200 || !data) throw new Error('Portail indisponible'); // page d'erreur avec « Réessayer »

  return (
    <main id="contenu" className="px-6 py-12 lg:py-16">
      <div className="mx-auto max-w-7xl"><DashboardApp user={data.user} items={data.items} preview={data.preview} /></div>
    </main>
  );
}
