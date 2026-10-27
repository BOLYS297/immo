export const DOMAIN_KEYS = ['ia', 'web', 'infra'] as const;
export type DomainKey = (typeof DOMAIN_KEYS)[number];
