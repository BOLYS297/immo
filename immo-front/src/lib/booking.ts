export const NEED_KEYS = ['ia', 'web', 'infra'] as const;
export type NeedKey = (typeof NEED_KEYS)[number];
export const DEADLINE_KEYS = ['urgent', 'month', 'later'] as const;
export type DeadlineKey = (typeof DEADLINE_KEYS)[number];

const TIMES = ['09:00', '10:30', '11:30', '14:00', '15:30', '16:30'];

/** Créneaux de 30 min sur les 5 prochains jours ouvrés, heure de Douala (UTC+1, sans changement d'heure). */
export function generateSlots(now: number = Date.now()): string[] {
  const local = new Date(now + 3_600_000);
  const d = new Date(Date.UTC(local.getUTCFullYear(), local.getUTCMonth(), local.getUTCDate()));
  const out: string[] = [];
  let days = 0;
  while (days < 5) {
    d.setUTCDate(d.getUTCDate() + 1);
    const dow = d.getUTCDay();
    if (dow === 0 || dow === 6) continue;
    const day = d.toISOString().slice(0, 10);
    for (const t of TIMES) out.push(`${day}T${t}:00+01:00`);
    days += 1;
  }
  return out;
}
