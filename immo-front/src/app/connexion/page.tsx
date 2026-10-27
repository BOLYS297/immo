import type { Metadata } from 'next';
import { redirect } from 'next/navigation';
import LoginForm from '@/components/auth/LoginForm';
import { getDict } from '@/lib/locale';
import { getClientSession } from '@/server/auth';

export function generateMetadata(): Metadata {
  return { title: getDict().pages.portalLogin.metaTitle, robots: { index: false, follow: false } };
}

export default async function ConnexionPage() {
  if (await getClientSession()) redirect('/dashboard');
  const p = getDict().pages.portalLogin;
  return (
    <main id="contenu" className="px-6 py-24">
      <div className="mx-auto max-w-md">
        <h1 className="text-4xl font-semibold tracking-tighter text-immo-dark dark:text-white">{p.title}</h1>
        <p className="mt-4 text-immo-dark/85 dark:text-immo-satin/90">{p.intro}</p>
        <LoginForm />
      </div>
    </main>
  );
}
