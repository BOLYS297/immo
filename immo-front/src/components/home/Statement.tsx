'use client';
import { useRef } from 'react';
import { useI18n } from '@/components/I18nProvider';
import type { BlockContent } from '@/lib/blocks';
import { usePrefersReducedMotion } from '@/lib/use-reduced-motion';
import { motion, useScroll, useTransform, type MotionValue } from 'framer-motion';

function Word({ w, p, range }: { w: string; p: MotionValue<number>; range: [number, number] }) {
  const opacity = useTransform(p, range, [0.3, 1]);
  return <motion.span style={{ opacity }} className="mr-[0.26em] inline-block">{w}</motion.span>;
}

export default function Statement({ content }: { content?: BlockContent<'statement'> }) {
  const { t } = useI18n();
  const TEXT = content?.text ?? t.statement.text;
  const ref = useRef<HTMLParagraphElement>(null);
  const reduce = usePrefersReducedMotion();
  const { scrollYProgress } = useScroll({ target: ref, offset: ['start 0.9', 'end 0.55'] });
  const words = TEXT.split(' ');
  return (
    <section aria-label={t.statement.aria} className="relative z-10 -mt-12 overflow-hidden rounded-t-[2.5rem] bg-immo-dark px-6 py-28 dark:bg-black/30 lg:py-40">
      <div aria-hidden className="absolute inset-0 opacity-[0.05]" style={{ backgroundImage: 'url(/images/symbol-mark.png)', backgroundSize: '130px' }} />
      <p ref={ref} className="relative mx-auto max-w-5xl text-3xl font-bold leading-[1.18] tracking-tight text-white sm:text-4xl lg:text-5xl">
        {reduce ? TEXT : words.map((w, i) => <Word key={i} w={w} p={scrollYProgress} range={[i / words.length, (i + 1) / words.length]} />)}
      </p>
    </section>
  );
}
