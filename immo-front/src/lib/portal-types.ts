// Contrat des éléments du portail renvoyés par Laravel (GET /portal/me) : garder aligné sur immo-back/config/portal.php.
export const ITEM_TYPES = ['task', 'milestone', 'deliverable', 'document', 'ticket'] as const;
export type ItemType = (typeof ITEM_TYPES)[number];
export const DOC_KINDS = ['quote', 'contract', 'invoice'] as const;
export const DOC_STATUSES = ['toSign', 'signed', 'paid', 'pending'] as const;
export const TICKET_STATUSES = ['open', 'progress', 'resolved'] as const;

type Base = { id: string; clientId: string; createdAt: string };
export type Task = Base & { type: 'task'; title: string; owner: string; col: number };
export type Milestone = Base & { type: 'milestone'; title: string; due: string; progress: number };
export type Deliverable = Base & { type: 'deliverable'; title: string; note: string; url: string };
export type DocItem = Base & { type: 'document'; title: string; kind: (typeof DOC_KINDS)[number]; amount: number | null; status: (typeof DOC_STATUSES)[number]; signedAt?: string };
export type Ticket = Base & { type: 'ticket'; title: string; message: string; priority: number; status: (typeof TICKET_STATUSES)[number]; reply: string };
export type Item = Task | Milestone | Deliverable | DocItem | Ticket;
export type PortalUser = { id: string; name: string; company: string; email: string };
