import type { Locale } from '@/lib/i18n';

/**
 * Informations juridiques de l'entreprise. Toute valeur `null` s'affiche « À compléter » sur le site :
 * renseignez-les (avec votre conseil juridique) avant la mise en ligne.
 */
export const COMPANY = {
  legalName: 'IMMO Digital',
  legalForm: null as string | null, // ex. SARL, SAS…
  capital: null as string | null, // ex. « 1 000 000 XAF »
  rccm: null as string | null, // n° RCCM
  niu: null as string | null, // Numéro d'Identifiant Unique (impôts)
  address: 'Douala, Yassa (à côté de Eneo), Cameroun',
  director: null as string | null, // directeur de la publication
  host: null as string | null, // hébergeur : nom, adresse, téléphone
  dataRetention: { leads: null as string | null, bookings: null as string | null, accounts: null as string | null },
};

/** Segments de texte : chaîne simple, valeur juridique (null = à compléter), ou coordonnée réglée dans l'admin (« Réglages du site »). */
export type Part = string | { value: string | null } | { contact: 'email' | 'phones' };
export type LegalSection = { title: string; body: Part[][] };
export type LegalDoc = { metaTitle: string; title: string; updated: string; intro: string; todo: string; sections: LegalSection[] };

const v = (value: string | null): Part => ({ value });
const email = (): Part => ({ contact: 'email' });
const phones: Part = { contact: 'phones' };
const UPDATED = '2026-10-19';

const fr = {
  notice: {
    metaTitle: 'Mentions légales — IMMO Digital',
    title: 'Mentions légales',
    updated: UPDATED,
    intro: 'Informations relatives à l’éditeur et à l’hébergeur du site, conformément à la loi n° 2010/021 du 21 décembre 2010 régissant le commerce électronique au Cameroun.',
    todo: 'À compléter',
    sections: [
      { title: 'Éditeur du site', body: [
        ['Raison sociale : ', v(COMPANY.legalName)], ['Forme juridique : ', v(COMPANY.legalForm)], ['Capital social : ', v(COMPANY.capital)],
        ['RCCM : ', v(COMPANY.rccm)], ['NIU : ', v(COMPANY.niu)], ['Siège : ', v(COMPANY.address)],
        ['Téléphone : ', phones], ['Email : ', email()], ['Directeur de la publication : ', v(COMPANY.director)],
      ] },
      { title: 'Hébergement', body: [['Hébergeur : ', v(COMPANY.host)]] },
      { title: 'Propriété intellectuelle', body: [
        ['Le nom IMMO Digital, le logo, le symbole, la charte graphique, les textes et le code de ce site sont la propriété d’IMMO Digital. Toute reproduction ou réutilisation sans autorisation écrite est interdite.'],
      ] },
      { title: 'Responsabilité', body: [
        ['Les informations publiées sur ce site sont fournies à titre indicatif. Les engagements d’IMMO Digital envers ses clients sont exclusivement ceux figurant dans les devis et contrats signés.'],
        ['Les réponses de l’agent IMMO sont générées automatiquement : elles ne constituent ni un devis ni un engagement contractuel.'],
      ] },
      { title: 'Données personnelles', body: [['Le traitement de vos données est décrit dans notre politique de confidentialité, accessible depuis le pied de page.']] },
    ],
  },
  privacy: {
    metaTitle: 'Politique de confidentialité — IMMO Digital',
    title: 'Politique de confidentialité',
    updated: UPDATED,
    intro: 'Cette page décrit les données que nous collectons, pourquoi, et comment exercer vos droits, conformément à la loi n° 2024/017 du 23 décembre 2024 relative à la protection des données à caractère personnel au Cameroun.',
    todo: 'À compléter',
    sections: [
      { title: 'Responsable du traitement', body: [['IMMO Digital, ', v(COMPANY.address), '. Contact : ', email(), '.']] },
      { title: 'Données collectées et finalités', body: [
        ['Demande de chiffrage (/services) : nom, email professionnel, entreprise, domaine et description du projet, pour vous recontacter et établir un devis.'],
        ['Prise de rendez-vous (/contact) : besoin, échéance, créneau choisi, nom, email et entreprise, pour organiser la session de cadrage.'],
        ['Diagnostic express : le nom d’entreprise ou de projet saisi, pour produire le bilan affiché. Il n’est pas enregistré dans notre base.'],
        ['Agent IMMO : vos messages sont transmis à notre fournisseur d’IA pour générer la réponse. Nous ne les conservons pas. Évitez d’y saisir des informations confidentielles.'],
        ['Espace client : nom, entreprise, email, mot de passe (stocké uniquement sous forme chiffrée irréversible), tickets de support et acceptations de documents horodatées, pour exécuter le contrat qui nous lie.'],
      ] },
      { title: 'Base légale', body: [['Votre consentement lorsque vous envoyez un formulaire, et l’exécution du contrat pour l’espace client.']] },
      { title: 'Durées de conservation', body: [
        ['Demandes de chiffrage : ', v(COMPANY.dataRetention.leads), '.'], ['Rendez-vous : ', v(COMPANY.dataRetention.bookings), '.'], ['Comptes clients : ', v(COMPANY.dataRetention.accounts), '.'],
      ] },
      { title: 'Destinataires et sous-traitants', body: [
        ['Vos données sont réservées à l’équipe IMMO Digital. Nous faisons appel à des prestataires techniques : hébergement du site et de la base de données (', v(COMPANY.host), '), envoi d’emails de notification (Resend), automatisations (n8n) et génération des réponses de l’agent (Anthropic).'],
        ['Certains de ces prestataires sont situés hors du Cameroun. Ces transferts sont limités aux données nécessaires au service rendu.'],
        ['Nous ne vendons ni ne louons aucune donnée.'],
      ] },
      { title: 'Cookies et stockage local', body: [
        ['immo-locale : mémorise la langue choisie (1 an).'], ['immo-session : maintient la connexion à l’espace client ou à l’administration (7 jours, uniquement après connexion).'],
        ['immo-theme (stockage local du navigateur) : mémorise le mode clair ou sombre.'],
        ['Aucun cookie publicitaire ni outil de mesure d’audience tiers n’est utilisé. Ces éléments étant strictement nécessaires au fonctionnement du site, ils ne requièrent pas de consentement.'],
      ] },
      { title: 'Sécurité', body: [['Mots de passe hachés (scrypt), session signée dans un cookie inaccessible aux scripts, connexion chiffrée (HTTPS), isolation stricte des données de chaque client et limitation des tentatives de connexion.']] },
      { title: 'Vos droits', body: [
        ['Vous disposez d’un droit d’accès, de rectification, d’opposition et de suppression de vos données. Pour l’exercer, écrivez-nous à ', email(), ' en précisant votre demande.'],
        ['Si vous estimez que vos droits ne sont pas respectés, vous pouvez saisir l’autorité de protection des données à caractère personnel compétente.'],
      ] },
    ],
  },
} satisfies Record<'notice' | 'privacy', LegalDoc>;

const en: typeof fr = {
  notice: {
    metaTitle: 'Legal notice — IMMO Digital',
    title: 'Legal notice',
    updated: UPDATED,
    intro: 'Information about the publisher and host of this website, in accordance with Cameroon Law No. 2010/021 of 21 December 2010 governing electronic commerce.',
    todo: 'To be completed',
    sections: [
      { title: 'Publisher', body: [
        ['Company name: ', v(COMPANY.legalName)], ['Legal form: ', v(COMPANY.legalForm)], ['Share capital: ', v(COMPANY.capital)],
        ['Trade register (RCCM): ', v(COMPANY.rccm)], ['Tax ID (NIU): ', v(COMPANY.niu)], ['Registered office: ', v(COMPANY.address)],
        ['Phone: ', phones], ['Email: ', email()], ['Publication director: ', v(COMPANY.director)],
      ] },
      { title: 'Hosting', body: [['Host: ', v(COMPANY.host)]] },
      { title: 'Intellectual property', body: [
        ['The IMMO Digital name, logo, symbol, brand guidelines, texts and code of this website are the property of IMMO Digital. Any reproduction or reuse without written permission is prohibited.'],
      ] },
      { title: 'Liability', body: [
        ['Information on this website is provided for guidance only. IMMO Digital’s commitments to its clients are exclusively those set out in signed quotes and contracts.'],
        ['Replies from the IMMO agent are generated automatically: they are neither a quote nor a contractual commitment.'],
      ] },
      { title: 'Personal data', body: [['How we process your data is described in our privacy policy, linked in the footer.']] },
    ],
  },
  privacy: {
    metaTitle: 'Privacy policy — IMMO Digital',
    title: 'Privacy policy',
    updated: UPDATED,
    intro: 'This page explains what data we collect, why, and how to exercise your rights, in accordance with Cameroon Law No. 2024/017 of 23 December 2024 on the protection of personal data.',
    todo: 'To be completed',
    sections: [
      { title: 'Data controller', body: [['IMMO Digital, ', v(COMPANY.address), '. Contact: ', email(), '.']] },
      { title: 'Data collected and purposes', body: [
        ['Quote request (/services): name, work email, company, area and project description, to get back to you and prepare a quote.'],
        ['Meeting booking (/contact): need, timeline, chosen slot, name, email and company, to organise the scoping session.'],
        ['Express diagnostic: the company or project name you enter, to produce the displayed report. It is not stored in our database.'],
        ['IMMO agent: your messages are sent to our AI provider to generate the reply. We do not keep them. Avoid entering confidential information.'],
        ['Client portal: name, company, email, password (stored only as an irreversible hash), support tickets and time-stamped document acceptances, to perform our contract with you.'],
      ] },
      { title: 'Legal basis', body: [['Your consent when you submit a form, and performance of the contract for the client portal.']] },
      { title: 'Retention periods', body: [
        ['Quote requests: ', v(COMPANY.dataRetention.leads), '.'], ['Bookings: ', v(COMPANY.dataRetention.bookings), '.'], ['Client accounts: ', v(COMPANY.dataRetention.accounts), '.'],
      ] },
      { title: 'Recipients and processors', body: [
        ['Your data is only accessible to the IMMO Digital team. We use technical providers: website and database hosting (', v(COMPANY.host), '), notification emails (Resend), automations (n8n) and agent replies (Anthropic).'],
        ['Some of these providers are located outside Cameroon. Transfers are limited to the data needed for the service.'],
        ['We never sell or rent any data.'],
      ] },
      { title: 'Cookies and local storage', body: [
        ['immo-locale: remembers your chosen language (1 year).'], ['immo-session: keeps you signed in to the client portal or admin (7 days, only after signing in).'],
        ['immo-theme (browser local storage): remembers light or dark mode.'],
        ['No advertising cookies or third-party analytics are used. As these items are strictly necessary for the site to work, they do not require consent.'],
      ] },
      { title: 'Security', body: [['Hashed passwords (scrypt), signed session in a cookie scripts cannot read, encrypted connection (HTTPS), strict isolation of each client’s data and rate-limited sign-in attempts.']] },
      { title: 'Your rights', body: [
        ['You have the right to access, correct, object to and delete your data. To exercise it, email us at ', email(), ' with your request.'],
        ['If you believe your rights are not respected, you may contact the competent personal data protection authority.'],
      ] },
    ],
  },
};

export const LEGAL: Record<Locale, typeof fr> = { fr, en };
