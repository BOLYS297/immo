'use client';
import { useState } from 'react';
import { FolderGit2, KanbanSquare, LifeBuoy, Receipt, UserCog, type LucideIcon } from 'lucide-react';
import LogoutButton from '@/components/auth/LogoutButton';
import { useI18n } from '@/components/I18nProvider';
import type { DocItem, Deliverable, Item, Milestone, PortalUser, Task, Ticket } from '@/lib/portal-types';
import AccountModule from './AccountModule';
import BillingModule from './BillingModule';
import DeliverablesModule from './DeliverablesModule';
import KanbanModule from './KanbanModule';
import SupportModule from './SupportModule';

type TabId = 'kanban' | 'deliverables' | 'billing' | 'support' | 'account';
const TABS: { id: TabId; icon: LucideIcon }[] = [{ id: 'kanban', icon: KanbanSquare }, { id: 'deliverables', icon: FolderGit2 }, { id: 'billing', icon: Receipt }, { id: 'support', icon: LifeBuoy }, { id: 'account', icon: UserCog }];

export default function DashboardApp({ user, items, preview }: { user: PortalUser; items: Item[]; preview: boolean }) {
  const d = useI18n().t.pages.dashboard;
  const [tab, setTab] = useState<TabId>('kanban');
  const of = <T extends Item>(type: T['type']) => items.filter((i): i is T => i.type === type);
  const tabs = TABS.filter((t) => !(preview && t.id === 'account'));

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-4xl font-semibold tracking-tighter text-immo-dark sm:text-5xl dark:text-white">{d.welcome.replace('{name}', user.name)}</h1>
          {user.company && <p className="mt-2 text-immo-dark/70 dark:text-immo-satin/70">{user.company}</p>}
        </div>
        {!preview && <LogoutButton to="/connexion" />}
      </div>
      {preview && <p role="note" className="mt-4 rounded-xl border border-immo-cyan bg-immo-sky/40 px-4 py-3 text-sm font-medium dark:bg-white/10">{d.preview.replace('{name}', user.name)}</p>}

      <div role="tablist" aria-label={d.tablist} className="mt-8 flex gap-2 overflow-x-auto border-b border-immo-dark/15 pb-3 dark:border-white/15">
        {tabs.map(({ id, icon: Icon }) => (
          <button key={id} role="tab" id={`dash-tab-${id}`} aria-selected={tab === id} aria-controls="dash-panel" onClick={() => setTab(id)}
            className={`inline-flex shrink-0 items-center gap-2 rounded-full px-5 py-2.5 font-semibold transition-colors ${tab === id ? 'bg-immo-cyan text-immo-dark' : 'hover:bg-immo-sky/50 dark:hover:bg-white/10'}`}>
            <Icon size={18} aria-hidden /> {d.tabs[TABS.findIndex((t) => t.id === id)]}
          </button>
        ))}
      </div>
      <div id="dash-panel" role="tabpanel" aria-labelledby={`dash-tab-${tab}`} className="mt-8">
        {tab === 'kanban' && <KanbanModule tasks={of<Task>('task')} milestones={of<Milestone>('milestone')} />}
        {tab === 'deliverables' && <DeliverablesModule items={of<Deliverable>('deliverable')} />}
        {tab === 'billing' && <BillingModule docs={of<DocItem>('document')} readOnly={preview} />}
        {tab === 'support' && <SupportModule tickets={of<Ticket>('ticket')} readOnly={preview} />}
        {tab === 'account' && <AccountModule />}
      </div>
    </div>
  );
}
