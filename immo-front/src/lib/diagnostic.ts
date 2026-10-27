import { dictionaries } from './dictionaries';
import type { Locale } from './i18n';

export type DiagnosticAxis = {
  id: 'automatisation' | 'application' | 'infrastructure';
  pole: string;
  title: string;
  detail: string;
};
export type DiagnosticReport = { subject: string; axes: DiagnosticAxis[] };

const IDS: DiagnosticAxis['id'][] = ['automatisation', 'application', 'infrastructure'];

export function buildDemoReport(query: string, locale: Locale): DiagnosticReport {
  const isUrl = /^(https?:\/\/)?[\w-]+(\.[\w-]+)+/.test(query);
  const subject = isUrl ? query.replace(/^https?:\/\//, '').replace(/\/.*$/, '') : query;
  return {
    subject,
    axes: dictionaries[locale].report.axes.map((a, i) => ({ id: IDS[i], pole: a.pole, title: a.title, detail: a.detail.replace('{name}', subject) })),
  };
}
