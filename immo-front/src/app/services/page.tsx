import { SystemPage, systemMetadata } from '@/server/system-page';

export const generateMetadata = () => systemMetadata('services');

// ?domaine=ia|web|infra présélectionne le pôle du formulaire de chiffrage.
export default function Page({ searchParams }: { searchParams: { domaine?: string } }) {
  return <SystemPage slug="services" searchParams={{ domaine: searchParams.domaine }} />;
}
