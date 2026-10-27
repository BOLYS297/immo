import { SystemPage, systemMetadata } from '@/server/system-page';

export const generateMetadata = () => systemMetadata('recommandations');

export default function Page() {
  return <SystemPage slug="recommandations" />;
}
