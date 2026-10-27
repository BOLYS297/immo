'use client';
import { useEffect, useRef } from 'react';
import { useI18n } from '@/components/I18nProvider';
import { useIsDark } from '@/lib/use-theme';

type P = { x: number; y: number; vx: number; vy: number; tx: number; ty: number; X: number; Y: number; Z: number; c: string; d: number; s: number; a: number };

const DEPTH = 0.055; // demi-épaisseur du symbole extrudé (unités du symbole, largeur = 1)

/**
 * Nuage de points 3D : les idées se rassemblent en symbole IMMO (face avant + face arrière = volume),
 * qui pivote seul puis suit le curseur.
 * Mouvement réduit : symbole déjà formé, aucune animation automatique ; il ne pivote qu'au survol ou au toucher.
 * `alwaysAnimate` (admin, Réglages du site → Affichage) : animation complète pour tous, préférence du visiteur ignorée.
 */
export default function SymbolField({ alwaysAnimate = false }: { alwaysAnimate?: boolean }) {
  const { t } = useI18n();
  const dark = useIsDark();
  const box = useRef<HTMLDivElement>(null);
  const cv = useRef<HTMLCanvasElement>(null);

  useEffect(() => {
    const wrap = box.current;
    const canvas = cv.current;
    const ctx = canvas?.getContext('2d');
    if (!wrap || !canvas || !ctx) return;
    const reduce = !alwaysAnimate && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const mouse = { x: -9999, y: -9999 };
    let parts: P[] = []; // faces arrière d'abord : dessinées en premier, elles passent derrière
    let samples: { X: number; Y: number; Z: number; c: string; back: boolean }[] = [];
    let w = 0, h = 0, raf = 0, t = 0, ax = 0, ay = 0, size = 0, visible = false;

    const draw = () => {
      ctx.clearRect(0, 0, w, h);
      for (const p of parts) { ctx.globalAlpha = p.a; ctx.fillStyle = p.c; ctx.fillRect(p.x, p.y, p.s, p.s); }
      ctx.globalAlpha = 1;
    };

    const project = () => {
      const cy = Math.cos(ay), sy = Math.sin(ay), cx = Math.cos(ax), sx = Math.sin(ax);
      for (const p of parts) {
        const x1 = p.X * cy + p.Z * sy, z1 = -p.X * sy + p.Z * cy;
        const y1 = p.Y * cx - z1 * sx, z2 = p.Y * sx + z1 * cx;
        const k = 1 / (1 - z2 * 0.9); // perspective : plus proche = plus grand
        p.tx = w / 2 + x1 * size * k; p.ty = h / 2 + y1 * size * k;
        p.s = 0.9 + k * 1.1; p.a = Math.min(1, Math.max(0.35, 0.55 + z2 * 3));
      }
    };

    const layout = () => {
      const r = wrap.getBoundingClientRect();
      const dpr = Math.min(window.devicePixelRatio || 1, 2);
      w = r.width; h = r.height; size = Math.min(w, h) * 0.82;
      canvas.width = w * dpr; canvas.height = h * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      if (!parts.length) {
        parts = samples.map((s) => ({ x: Math.random() * w, y: Math.random() * h, vx: 0, vy: 0, tx: 0, ty: 0, X: s.X, Y: s.Y, Z: s.Z, c: s.c, d: Math.random() * 50, s: 2, a: 1 }));
      }
      project();
      if (reduce) { parts.forEach((p) => { p.x = p.tx; p.y = p.ty; }); draw(); }
    };

    const tick = () => {
      t += 1;
      const hover = mouse.x > -9000;
      // Seul : rotation lente qui montre le volume. Survol : le symbole regarde le curseur. Mouvement réduit : immobile hors survol.
      const tay = hover ? (mouse.x / w - 0.5) * 1.3 : reduce ? 0 : Math.sin(t * 0.009) * 0.55;
      const tax = hover ? -(mouse.y / h - 0.5) * 0.8 : reduce ? 0 : Math.cos(t * 0.007) * 0.18;
      ay += (tay - ay) * 0.06; ax += (tax - ax) * 0.06;
      project();
      for (const p of parts) {
        if (reduce) { p.x = p.tx; p.y = p.ty; continue; }
        if (t > p.d) {
          p.vx += (p.tx - p.x) * 0.014; p.vy += (p.ty - p.y) * 0.014;
          const mx = p.x - mouse.x, my = p.y - mouse.y, d2 = mx * mx + my * my;
          if (d2 < 6000) { const dist = Math.sqrt(d2) || 1, f = (1 - d2 / 6000) * 1.6; p.vx += (mx / dist) * f; p.vy += (my / dist) * f; }
          p.vx *= 0.87; p.vy *= 0.87; p.x += p.vx; p.y += p.vy;
        }
      }
      draw();
      // Mouvement réduit : on s'arrête dès que le symbole est revenu de face.
      const settled = reduce && !hover && Math.abs(ay) < 0.002 && Math.abs(ax) < 0.002;
      raf = visible && !settled ? requestAnimationFrame(tick) : 0;
    };
    const start = () => { if (visible && !raf && samples.length) raf = requestAnimationFrame(tick); };

    const io = new IntersectionObserver(([e]) => {
      visible = e.isIntersecting;
      cancelAnimationFrame(raf);
      raf = 0;
      if (!reduce) start();
    });
    const img = new Image();
    img.src = '/images/symbol-mark.png';
    img.onload = () => {
      const N = 200, off = document.createElement('canvas');
      off.width = N; off.height = N;
      const o = off.getContext('2d');
      if (!o) return;
      o.drawImage(img, 0, 0, N, N);
      const data = o.getImageData(0, 0, N, N).data;
      const lift = (v: number) => (dark ? Math.round(v + (255 - v) * 0.5) : v);
      // Face arrière teintée vers le Bleu Nuit #273480 (clair) ou atténuée (sombre) : elle fait voir l'épaisseur en rotation.
      const NIGHT = [39, 52, 128];
      const shade = (v: number, ch: number) => (dark ? Math.round(lift(v) * 0.6) : Math.round(v * 0.5 + NIGHT[ch] * 0.5));
      const front: typeof samples = [], back: typeof samples = [];
      for (let y = 0; y < N; y += 2) for (let x = 0; x < N; x += 2) {
        const i = (y * N + x) * 4;
        if (data[i + 3] <= 140) continue;
        const X = x / N - 0.5, Y = y / N - 0.5;
        const lum = (0.3 * data[i] + 0.59 * data[i + 1] + 0.11 * data[i + 2]) / 255;
        // Légère courbure (centre en avant) + relief selon la teinte + grain.
        const Z = (0.5 - Math.hypot(X, Y)) * 0.18 + (lum - 0.6) * 0.08 + (Math.random() - 0.5) * 0.02;
        const [r, g, b] = [data[i], data[i + 1], data[i + 2]];
        front.push({ X, Y, Z: Z + DEPTH, c: `rgb(${lift(r)},${lift(g)},${lift(b)})`, back: false });
        if ((x + y) % 4 === 0) back.push({ X, Y, Z: Z - DEPTH, c: `rgb(${shade(r, 0)},${shade(g, 1)},${shade(b, 2)})`, back: true });
      }
      samples = [...back, ...front];
      layout();
      io.observe(wrap);
    };
    const ro = new ResizeObserver(() => samples.length && layout());
    ro.observe(wrap);

    const move = (e: PointerEvent) => { const r = wrap.getBoundingClientRect(); mouse.x = e.clientX - r.left; mouse.y = e.clientY - r.top; start(); };
    const leave = () => { mouse.x = mouse.y = -9999; start(); };
    const burst = () => { if (!reduce) parts.forEach((p) => { p.vx += (Math.random() - 0.5) * 34; p.vy += (Math.random() - 0.5) * 34; }); };
    wrap.addEventListener('pointermove', move);
    wrap.addEventListener('pointerleave', leave);
    wrap.addEventListener('pointerdown', burst);
    return () => {
      cancelAnimationFrame(raf); io.disconnect(); ro.disconnect();
      wrap.removeEventListener('pointermove', move); wrap.removeEventListener('pointerleave', leave); wrap.removeEventListener('pointerdown', burst);
    };
  }, [dark, alwaysAnimate]);

  return (
    <div ref={box} role="img" aria-label={t.symbol.alt} className="h-full w-full touch-pan-y">
      <canvas ref={cv} aria-hidden className="block h-full w-full" />
    </div>
  );
}
