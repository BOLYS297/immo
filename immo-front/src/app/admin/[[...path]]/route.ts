import { NextResponse } from 'next/server';
import { cmsAdminUrl } from '@/server/cms-api';
import { siteUrl } from '@/server/site-url';

/** L'administration du site est dans Laravel : /admin (anciens favoris compris) y renvoie. */
export function GET(req: Request) {
  return NextResponse.redirect(cmsAdminUrl() ?? siteUrl('/', req));
}
