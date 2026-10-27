import { existsSync } from 'node:fs';
import path from 'node:path';

/** true si les fichiers Gilroy sous licence ont été déposés dans public/fonts. Sans eux : Outfit, sans aucune requête 404. */
export const HAS_GILROY = existsSync(path.join(process.cwd(), 'public', 'fonts', 'Gilroy-Regular.woff2'));
