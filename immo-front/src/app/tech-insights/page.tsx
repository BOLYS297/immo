import { SystemPage, systemMetadata } from '@/server/system-page';

export const generateMetadata = () => systemMetadata('tech-insights');

export default function Page() {
  return <SystemPage slug="tech-insights" />;
}
