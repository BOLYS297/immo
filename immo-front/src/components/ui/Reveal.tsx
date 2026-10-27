'use client';
import { motion } from 'framer-motion';
import { usePrefersReducedMotion } from '@/lib/use-reduced-motion';

export default function Reveal({ children, delay = 0, className }: { children: React.ReactNode; delay?: number; className?: string }) {
  // Même état de départ côté serveur et client (hydratation identique) ; mouvement réduit = apparition instantanée.
  const reduce = usePrefersReducedMotion();
  return (
    <motion.div
      className={className}
      initial={{ opacity: 0, y: 28 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: '-60px' }}
      transition={reduce ? { duration: 0 } : { duration: 0.7, delay, ease: [0.22, 1, 0.36, 1] }}
    >
      {children}
    </motion.div>
  );
}
