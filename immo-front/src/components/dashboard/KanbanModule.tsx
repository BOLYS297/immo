'use client';
import { useI18n } from '@/components/I18nProvider';
import type { Milestone, Task } from '@/lib/portal-types';

export default function KanbanModule({ tasks, milestones }: { tasks: Task[]; milestones: Milestone[] }) {
  const { t } = useI18n();
  const k = t.pages.dashboard.kanban;
  return (
    <div className="space-y-10">
      <section aria-labelledby="jalons-title">
        <h2 id="jalons-title" className="text-xl font-semibold text-immo-dark dark:text-white">{k.milestones}</h2>
        {milestones.length === 0 ? <p className="mt-4">{t.pages.dashboard.empty}</p> : (
          <ul className="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            {milestones.map((m) => (
              <li key={m.id} className="rounded-2xl border border-immo-dark/15 bg-white/70 p-5 dark:border-white/15 dark:bg-white/5">
                <p className="font-semibold">{m.title}</p>
                <p className="text-sm text-immo-dark/70 dark:text-immo-satin/70">{m.due}</p>
                <div role="progressbar" aria-valuenow={m.progress} aria-valuemin={0} aria-valuemax={100} aria-label={`${k.progress}${m.title}`} className="mt-3 h-2 rounded-full bg-immo-sky/60 dark:bg-white/15">
                  <div className="h-2 rounded-full bg-immo-cyan" style={{ width: `${m.progress}%` }} />
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>
      <section aria-labelledby="sprint-title">
        <h2 id="sprint-title" className="text-xl font-semibold text-immo-dark dark:text-white">{k.board}</h2>
        <div className="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          {k.cols.map((name, ci) => (
            <div key={name} className="rounded-2xl bg-white/70 p-4 dark:bg-white/5">
              <h3 className="mb-3 font-semibold">{name}</h3>
              <ul className="space-y-3">
                {tasks.filter((x) => x.col === ci).map((x) => (
                  <li key={x.id} className="rounded-xl border-l-4 border-immo-cyan bg-immo-satin p-3 dark:bg-white/10"><p className="font-medium">{x.title}</p>{x.owner && <p className="text-sm text-immo-dark/70 dark:text-immo-satin/70">{x.owner}</p>}</li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </section>
    </div>
  );
}
