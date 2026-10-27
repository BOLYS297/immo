import { timingSafeEqual } from 'node:crypto';
import { revalidateTag } from 'next/cache';
import { NextResponse } from 'next/server';

/** Appelée par le CMS Laravel (FrontRevalidator) après une modification : vide le cache des pages concernées. */
export async function POST(req: Request) {
  const secret = process.env.REVALIDATE_SECRET;
  const given = Buffer.from(req.headers.get('x-revalidate-secret') ?? '');
  if (!secret || given.length !== Buffer.byteLength(secret) || !timingSafeEqual(given, Buffer.from(secret))) {
    return NextResponse.json({ error: 'auth' }, { status: 401 });
  }
  const body = (await req.json().catch(() => null)) as { tags?: unknown } | null;
  const tags = Array.isArray(body?.tags) ? body.tags.filter((t): t is string => typeof t === 'string' && /^[a-z0-9:_-]{1,120}$/i.test(t)).slice(0, 20) : [];
  if (tags.length === 0) return NextResponse.json({ error: 'invalid' }, { status: 400 });
  tags.forEach((t) => revalidateTag(t));
  return NextResponse.json({ ok: true, revalidated: tags });
}
