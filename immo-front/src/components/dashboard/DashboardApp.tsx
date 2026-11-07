'use client';
import { useState } from 'react';
import { FolderGit2, KanbanSquare, LifeBuoy, Receipt, UserCog, type LucideIcon } from 'lucide-react';
import LogoutButton from '@/components/auth/LogoutButton';
import { useI18n } from '@/components/I18nProvider';
import type { DocItem, Deliverable, Item, Milestone, PortalUser, Project, Task, Ticket } from '@/lib/portal-types';
import AccountModule from './AccountModule';
import BillingModule from './BillingModule';
import DeliverablesModule from './DeliverablesModule';
import KanbanModule from './KanbanModule';
import SupportModule from './SupportModule';

type TabId = 'kanban' | 'deliverables' | 'billing' | 'support' | 'account';
const TABS: { id: TabId; icon: LucideIcon }[] = [{ id: 'kanban', icon: KanbanSquare }, { id: 'deliverables', icon: FolderGit2 }, { id: 'billing', icon: Receipt }, { id: 'support', icon: LifeBuoy }, { id: 'account', icon: UserCog }];
const field = 'rounded-xl border border-immo-dark/25 bg-white px-4 py-2.5 font-semibold text-immo-dark dark:border-white/20 dark:bg-white/10 dark:text-white';

/** Tableau de bord du client : un projet à la fois (le plus récent par défaut) ; « Mon compte » reste commun aux projets. */
export default function DashboardApp({ user, projects, items, preview }: { user: PortalUser; projects: Project[]; items: Item[]; preview: boolean }) {
  const d = useI18n().t.pages.dashboard;
  const [tab, setTab] = useState<TabId>('kanban');
  const [projectId, setProjectId] = useState(projects[0]?.id ?? '');
  const project = projects.find((p) => p.id === projectId) ?? projects[0];
  const of = <T extends Item>(type: T['type']) => items.filter((i): i is T => i.type === type && i.projectId === project?.id);
  const tabs = TABS.filter((t) => !(preview && t.id === 'account') && (project || t.id === 'account'));
  const current = tabs.some((t) => t.id === tab) ? tab : tabs[0]?.id;

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-4xl font-semibold tracking-tighter text-immo-dark sm:text-5xl dark:text-white">{d.welcome.replace('{name}', user.name)}</h1>
          {user.company && <p className="mt-2 text-immo-dark/70 dark:text-immo-satin/70">{user.company}</p>}
        </div>
        {!preview && <LogoutButton to="/connexion" />}
      </div>
      {/* Bandeau réservé à l'aperçu ouvert depuis l'admin ; « Quitter l'aperçu » révoque le jeton de lecture seule. */}
      {preview && (
        <div role="note" className="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-immo-cyan bg-immo-sky/40 px-4 py-3 text-sm font-medium dark:bg-white/10">
          <p>{d.preview.replace('{name}', user.name)}</p>
          <LogoutButton to="/connexion" label={d.exitPreview} />
        </div>
      )}

      {project ? (
        <div className="mt-8 flex flex-wrap items-end gap-x-6 gap-y-3 rounded-2xl border border-immo-dark/15 bg-white/70 p-5 dark:border-white/15 dark:bg-white/5">
          {projects.length > 1 ? (
            <label className="flex flex-col gap-1.5 text-sm font-semibold">
              {d.projects.label}
              <select value={project.id} onChange={(e) => setProjectId(e.target.value)} className={field}>
                {projects.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
              </select>
            </label>
          ) : (
            <div>
              <p className="text-sm font-semibold text-immo-dark/70 dark:text-immo-satin/70">{d.projects.label}</p>
              <p className="text-xl font-semibold text-immo-dark dark:text-white">{project.name}</p>
            </div>
          )}
          <span className="rounded-full bg-immo-sky/60 px-3 py-1 text-sm font-semibold text-immo-dark dark:bg-white/10 dark:text-immo-satin">{d.projects.statuses[project.status] ?? project.status}</span>
          {project.summary && <p className="basis-full text-immo-dark/80 dark:text-immo-satin/80">{project.summary}</p>}
        </div>
      ) : (
        <p className="mt-8 rounded-2xl border border-immo-dark/15 bg-white/70 p-5 dark:border-white/15 dark:bg-white/5">{d.projects.none}</p>
      )}

      {current && (
        <>
          <div role="tablist" aria-label={d.tablist} className="mt-8 flex gap-2 overflow-x-auto border-b border-immo-dark/15 pb-3 dark:border-white/15">
            {tabs.map(({ id, icon: Icon }) => (
              <button key={id} role="tab" id={`dash-tab-${id}`} aria-selected={current === id} aria-controls="dash-panel" onClick={() => setTab(id)}
                className={`inline-flex shrink-0 items-center gap-2 rounded-full px-5 py-2.5 font-semibold transition-colors ${current === id ? 'bg-immo-cyan text-immo-dark' : 'hover:bg-immo-sky/50 dark:hover:bg-white/10'}`}>
                <Icon size={18} aria-hidden /> {d.tabs[TABS.findIndex((t) => t.id === id)]}
              </button>
            ))}
          </div>
          <div id="dash-panel" role="tabpanel" aria-labelledby={`dash-tab-${current}`} className="mt-8">
            {current === 'kanban' && <KanbanModule tasks={of<Task>('task')} milestones={of<Milestone>('milestone')} />}
            {current === 'deliverables' && <DeliverablesModule items={of<Deliverable>('deliverable')} />}
            {current === 'billing' && <BillingModule docs={of<DocItem>('document')} readOnly={preview} />}
            {current === 'support' && project && <SupportModule tickets={of<Ticket>('ticket')} projectId={project.id} readOnly={preview} />}
            {current === 'account' && <AccountModule />}
          </div>
        </>
      )}
    </div>
  );
}
