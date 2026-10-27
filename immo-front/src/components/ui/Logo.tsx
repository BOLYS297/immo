import Image from 'next/image';

/** Logo officiel recomposé : symbole + filet + mot-symbole en texte net (jamais flou, jamais sur fond interdit). */
export default function Logo({ tone = 'auto', priority = false }: { tone?: 'auto' | 'dark'; priority?: boolean }) {
  return (
    <span className="inline-flex items-center gap-3">
      {tone === 'auto' && <Image src="/images/symbol-mark.png" alt="" width={44} height={45} priority={priority} className="h-11 w-auto dark:hidden" />}
      <Image src="/images/symbol-dark.png" alt="" width={44} height={45} priority={priority} className={tone === 'auto' ? 'hidden h-11 w-auto dark:block' : 'h-11 w-auto'} />
      <span aria-hidden className="h-9 w-px bg-current opacity-40" />
      {/* translate="no" : la marque reste « IMMO DIGITAL » même quand le navigateur traduit la page. */}
      <span aria-hidden translate="no" className="leading-none">
        <span className="block text-2xl font-bold tracking-wide">IMMO</span>
        <span className="mt-1 block text-[0.65rem] font-medium tracking-[0.32em]">DIGITAL</span>
      </span>
    </span>
  );
}
