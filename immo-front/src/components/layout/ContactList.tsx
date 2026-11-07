import { Mail, MapPin, MessageCircle, Phone } from 'lucide-react';
import type { Contact } from '@/lib/site';

/** Coordonnées du site (admin « Réglages du site ») : pied de page et page Contact. Une ligne vide n'est pas affichée. */
export default function ContactList({ contact, whatsappLabel, linkClass, className }: { contact: Contact; whatsappLabel: string; linkClass: string; className: string }) {
  const icon = 'mt-0.5 shrink-0 text-immo-cyan';
  return (
    <ul className={className}>
      {(contact.email || contact.emails.length > 0) && (
        <li className="flex gap-3">
          <Mail size={20} className={icon} aria-hidden />
          <span className="flex min-w-0 flex-col gap-1">
            {contact.email && <a href={`mailto:${contact.email}`} className={`break-all ${linkClass}`}>{contact.email}</a>}
            {contact.emails.map((e) => (
              <span key={e.address} className="break-all">
                {e.label && <span className="opacity-80">{e.label} : </span>}
                <a href={`mailto:${e.address}`} className={linkClass}>{e.address}</a>
              </span>
            ))}
          </span>
        </li>
      )}
      {contact.phones.length > 0 && (
        <li className="flex gap-3"><Phone size={20} className={icon} aria-hidden /><span className="flex flex-col">{contact.phones.map((p) => <a key={p.href} href={`tel:${p.href}`} className={linkClass}>{p.label}</a>)}</span></li>
      )}
      {contact.whatsappUrl && <li className="flex gap-3"><MessageCircle size={20} className={icon} aria-hidden /><a href={contact.whatsappUrl} target="_blank" rel="noopener noreferrer" className={linkClass}>{whatsappLabel}</a></li>}
      {contact.address.length > 0 && <li className="flex gap-3"><MapPin size={20} className={icon} aria-hidden /><span>{contact.address.map((l) => <span key={l} className="block">{l}</span>)}</span></li>}
    </ul>
  );
}
