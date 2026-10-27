'use client';
import { useState, type InputHTMLAttributes } from 'react';
import { Eye, EyeOff } from 'lucide-react';
import { useI18n } from '@/components/I18nProvider';

/**
 * Champ mot de passe avec bouton afficher / masquer. À placer dans le <label> du champ (le bouton, contenu
 * interactif, ne déclenche pas le label). `className` : classes du champ ; la place du bouton est réservée à droite.
 */
export default function PasswordInput({ className = '', ...props }: Omit<InputHTMLAttributes<HTMLInputElement>, 'type'>) {
  const { t } = useI18n();
  const [visible, setVisible] = useState(false);
  return (
    <span className="relative block">
      <input {...props} type={visible ? 'text' : 'password'} className={`${className} pr-12`} />
      <button
        type="button"
        onClick={() => setVisible((v) => !v)}
        aria-pressed={visible}
        aria-label={visible ? t.a11y.hidePassword : t.a11y.showPassword}
        title={visible ? t.a11y.hidePassword : t.a11y.showPassword}
        className="absolute inset-y-0 right-1.5 mt-1.5 grid w-10 place-items-center rounded-lg text-immo-dark/70 transition-colors hover:text-immo-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-immo-cyan dark:text-immo-satin/80 dark:hover:text-white"
      >
        {visible ? <EyeOff size={20} aria-hidden /> : <Eye size={20} aria-hidden />}
      </button>
    </span>
  );
}
