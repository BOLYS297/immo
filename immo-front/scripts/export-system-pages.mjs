// Génère immo-back/database/seeders/data/system-pages.json depuis src/lib/system-pages.ts et les dictionnaires,
// pour que le seeder Laravel crée les pages système avec exactement les textes du site.
// Usage : node scripts/export-system-pages.mjs (Node 22.6+ : lecture native du TypeScript).
import { cpSync, mkdtempSync, readFileSync, readdirSync, rmSync, writeFileSync, mkdirSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const out = join(root, '..', 'immo-back', 'database', 'seeders', 'data', 'system-pages.json');

// Copie temporaire : Node exige l'extension « .ts » dans les imports relatifs.
const tmp = mkdtempSync(join(tmpdir(), 'immo-pages-'));
try {
  cpSync(join(root, 'src', 'lib', 'dictionaries'), join(tmp, 'dictionaries'), { recursive: true });
  cpSync(join(root, 'src', 'lib', 'system-pages.ts'), join(tmp, 'system-pages.ts'));
  for (const file of readdirSync(join(tmp, 'dictionaries'))) {
    const path = join(tmp, 'dictionaries', file);
    writeFileSync(path, readFileSync(path, 'utf8').replace(/from '\.\/([\w-]+)'/g, "from './$1.ts'"));
  }
  const load = (p) => import(pathToFileURL(join(tmp, p)).href);
  const { fr } = await load('dictionaries/fr.ts');
  const { en } = await load('dictionaries/en.ts');
  const { SYSTEM_SLUGS, systemPage } = await load('system-pages.ts');

  const pages = {};
  for (const slug of SYSTEM_SLUGS) {
    const [f, e] = [systemPage(slug, fr), systemPage(slug, en)];
    pages[slug] = {
      title: { fr: f.title, en: e.title },
      meta_title: { fr: f.meta.title, en: e.meta.title },
      meta_description: { fr: f.meta.description, en: e.meta.description },
      sections: f.sections.map((s, i) => ({ type: s.type, settings: s.settings ?? null, content: { fr: s.content, en: e.sections[i].content } })),
    };
  }
  mkdirSync(dirname(out), { recursive: true });
  writeFileSync(out, JSON.stringify(pages, null, 2) + '\n');
  console.log(`${Object.keys(pages).length} pages écrites dans ${out}`);
} finally {
  rmSync(tmp, { recursive: true, force: true });
}
