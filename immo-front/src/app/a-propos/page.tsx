import { SystemPage, systemMetadata } from '@/server/system-page';

export const generateMetadata = () => systemMetadata('a-propos');

export default function Page() {
  return <SystemPage slug="a-propos" />;
}
