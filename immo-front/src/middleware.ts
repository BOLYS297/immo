import { NextResponse, type NextRequest } from 'next/server';
import { COOKIE, detectLocale, isLocale } from '@/lib/i18n';

// Langue = choix explicite (cookie) sinon langue du navigateur, détectée à chaque visite.
export function middleware(req: NextRequest) {
  const saved = req.cookies.get(COOKIE)?.value;
  const locale = isLocale(saved) ? saved : detectLocale(req.headers.get('accept-language'));
  const headers = new Headers(req.headers);
  headers.set('x-immo-locale', locale);
  return NextResponse.next({ request: { headers } });
}

export const config = { matcher: ['/((?!_next/static|_next/image|.*\\..*).*)'] };
