import { Fragment, type ReactNode } from 'react';
import Link from 'next/link';

/**
 * Markdown des réponses de l'agent IMMO, rendu sans HTML injecté (rien n'est inséré tel quel) et relu à chaque
 * morceau reçu pendant le streaming : titres (#, ##, ###), listes à puces (- ou *) et numérotées (1.), blocs de
 * code (```), paragraphes (retours à la ligne gardés), **gras**, *italique*, `code` et [liens](…).
 * Liens internes (/contact) dans le site ; liens externes dans un nouvel onglet ; autres formats : texte simple.
 */
type Block =
  | { type: 'h'; level: number; text: string }
  | { type: 'p'; lines: string[] }
  | { type: 'ul' | 'ol'; items: string[] }
  | { type: 'code'; text: string };

const BULLET = /^\s*[-*•]\s+/;
const NUMBER = /^\s*\d+[.)]\s+/;

function parse(source: string): Block[] {
  const blocks: Block[] = [];
  const lines = source.replace(/\r\n/g, '\n').split('\n');
  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    if (line.trim().startsWith('```')) {
      const code: string[] = [];
      for (i++; i < lines.length && !lines[i].trim().startsWith('```'); i++) code.push(lines[i]);
      blocks.push({ type: 'code', text: code.join('\n') }); // bloc encore ouvert pendant le streaming : affiché quand même
      continue;
    }
    if (!line.trim()) continue;
    const heading = /^(#{1,4})\s+(.*)$/.exec(line.trim());
    if (heading) { blocks.push({ type: 'h', level: heading[1].length, text: heading[2] }); continue; }
    const kind = BULLET.test(line) ? 'ul' : NUMBER.test(line) ? 'ol' : null;
    const prev = blocks[blocks.length - 1];
    if (kind) {
      const text = line.replace(kind === 'ul' ? BULLET : NUMBER, '');
      if (prev?.type === kind) prev.items.push(text); // y compris une liste aérée (ligne vide entre les éléments)
      else blocks.push({ type: kind, items: [text] });
      continue;
    }
    // Ligne de suite d'un élément de liste (indentée) ou d'un paragraphe.
    if ((prev?.type === 'ul' || prev?.type === 'ol') && /^\s{2,}\S/.test(line) && lines[i - 1]?.trim()) {
      prev.items[prev.items.length - 1] += ` ${line.trim()}`;
    } else if (prev?.type === 'p' && lines[i - 1]?.trim()) {
      prev.lines.push(line.trim());
    } else {
      blocks.push({ type: 'p', lines: [line.trim()] });
    }
  }
  return blocks;
}

const SAFE_HREF = /^(\/(?!\/)|https?:\/\/|mailto:|tel:)/i;
const INLINE = /\*\*([^*]+)\*\*|`([^`]+)`|\[([^\]]+)\]\(([^)\s]+)\)|(?<![\w*])\*([^*\n]+)\*(?![\w*])|(?<!\w)_([^_\n]+)_(?!\w)/g;
const LINK = 'font-semibold text-immo-sky underline decoration-immo-cyan decoration-2 underline-offset-4 hover:text-white';

function inline(text: string): ReactNode[] {
  const out: ReactNode[] = [];
  let last = 0;
  for (const m of text.matchAll(INLINE)) {
    if (m.index > last) out.push(text.slice(last, m.index));
    const key = m.index;
    if (m[1] !== undefined) out.push(<strong key={key} className="font-semibold text-white">{m[1]}</strong>);
    else if (m[2] !== undefined) out.push(<code key={key} className="rounded bg-black/30 px-1.5 py-0.5 font-mono text-[0.9em]">{m[2]}</code>);
    else if (m[3] !== undefined) {
      const href = m[4];
      if (!SAFE_HREF.test(href)) out.push(m[3]);
      else if (href.startsWith('/')) out.push(<Link key={key} href={href} className={LINK}>{m[3]}</Link>);
      else out.push(<a key={key} href={href} className={LINK} {...(/^https?:/i.test(href) && { target: '_blank', rel: 'noopener noreferrer' })}>{m[3]}</a>);
    } else out.push(<em key={key}>{m[5] ?? m[6]}</em>);
    last = m.index + m[0].length;
  }
  if (last < text.length) out.push(text.slice(last));
  return out;
}

export default function ChatMarkdown({ text }: { text: string }) {
  return (
    <div className="space-y-3 break-words">
      {parse(text).map((b, i) => (
        <Fragment key={i}>
          {b.type === 'h' ? <p className={`font-semibold text-white ${b.level <= 2 ? 'text-lg' : ''}`}>{inline(b.text)}</p>
            : b.type === 'p' ? <p>{b.lines.map((l, j) => <Fragment key={j}>{j > 0 && <br />}{inline(l)}</Fragment>)}</p>
            : b.type === 'code' ? <pre className="overflow-x-auto rounded-lg bg-black/30 p-3 font-mono text-sm"><code>{b.text}</code></pre>
            : b.type === 'ul' ? <ul className="list-disc space-y-1 pl-5 marker:text-immo-cyan">{b.items.map((it, j) => <li key={j}>{inline(it)}</li>)}</ul>
            : <ol className="list-decimal space-y-1 pl-5 marker:text-immo-cyan">{b.items.map((it, j) => <li key={j}>{inline(it)}</li>)}</ol>}
        </Fragment>
      ))}
    </div>
  );
}
