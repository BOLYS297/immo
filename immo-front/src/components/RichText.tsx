import { Fragment, type ReactNode } from 'react';
import Link from 'next/link';

/**
 * Texte enrichi saisi dans l'admin Laravel, sans HTML (rien n'est injecté tel quel) :
 * « ## » intertitre, lignes « - » liste, paragraphes séparés par une ligne vide,
 * **gras** et [texte du lien](/chemin, https://…, mailto:…, tel:…). Un lien d'un autre format reste du texte.
 */
type Block = { type: 'h'; text: string } | { type: 'p'; text: string } | { type: 'ul'; items: string[] };

export function parseRichText(body: string): Block[] {
  return body.replace(/\r\n/g, '\n').split(/\n{2,}/).map((c) => c.trim()).filter(Boolean).map((c): Block => {
    if (c.startsWith('## ')) return { type: 'h', text: c.slice(3).trim() };
    const lines = c.split('\n').map((l) => l.trim());
    if (lines.every((l) => l.startsWith('- '))) return { type: 'ul', items: lines.map((l) => l.slice(2).trim()) };
    return { type: 'p', text: lines.join(' ') };
  });
}

const SAFE_HREF = /^(\/(?!\/)|https?:\/\/|mailto:|tel:)/i;
const INLINE = /\*\*([^*]+)\*\*|\[([^\]]+)\]\(([^)\s]+)\)/g;

function inline(text: string, linkClass: string): ReactNode[] {
  const out: ReactNode[] = [];
  let last = 0;
  for (const m of text.matchAll(INLINE)) {
    if (m.index > last) out.push(text.slice(last, m.index));
    const key = `${m.index}`;
    if (m[1] !== undefined) out.push(<strong key={key} className="font-semibold">{m[1]}</strong>);
    else if (!SAFE_HREF.test(m[3])) out.push(m[2]);
    else if (m[3].startsWith('/')) out.push(<Link key={key} href={m[3]} className={linkClass}>{m[2]}</Link>);
    else out.push(<a key={key} href={m[3]} className={linkClass} {...(/^https?:/i.test(m[3]) && { target: '_blank', rel: 'noopener noreferrer' })}>{m[2]}</a>);
    last = m.index + m[0].length;
  }
  if (last < text.length) out.push(text.slice(last));
  return out;
}

type Props = {
  body: string;
  /** Niveau des intertitres : h2 dans un article, h3 dans une section qui a déjà son titre h2. */
  heading?: 'h2' | 'h3';
  /** Texte clair sur fond Bleu Nuit. */
  inverted?: boolean;
  className?: string;
};

export default function RichText({ body, heading: H = 'h3', inverted = false, className = 'space-y-4' }: Props) {
  const link = `font-semibold underline decoration-immo-cyan decoration-2 underline-offset-4 ${inverted ? 'text-white hover:text-immo-sky' : 'hover:text-immo-cyan'}`;
  const title = inverted ? 'text-white' : 'text-immo-dark dark:text-white';
  return (
    <div className={className}>
      {parseRichText(body).map((b, i) => (
        <Fragment key={i}>
          {b.type === 'h' ? <H className={`pt-3 text-2xl font-semibold tracking-tight ${title}`}>{inline(b.text, link)}</H>
            : b.type === 'p' ? <p>{inline(b.text, link)}</p>
            : <ul className="list-disc space-y-2 pl-6 marker:text-immo-cyan">{b.items.map((it, j) => <li key={j}>{inline(it, link)}</li>)}</ul>}
        </Fragment>
      ))}
    </div>
  );
}
