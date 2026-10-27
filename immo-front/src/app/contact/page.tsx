import { SystemPage, systemMetadata } from '@/server/system-page';

export const generateMetadata = () => systemMetadata('contact');

export default function Page() {
  return <SystemPage slug="contact" />;
}
