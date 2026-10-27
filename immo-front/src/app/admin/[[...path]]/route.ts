import { NextResponse } from 'next/server';
import { cmsAdminUrl } from '@/server/cms-api';

/** L'administration du site est dans Laravel : /admin (anciens favoris compris) y renvoie. */
export function GET(req: Request) {
  return NextResponse.redirect(cmsAdminUrl() ?? new URL('/', req.url));
}
