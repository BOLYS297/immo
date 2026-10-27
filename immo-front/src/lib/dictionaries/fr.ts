import { pagesFr } from './pages-fr';

export const fr = {
  pages: pagesFr,
  meta: {
    title: 'IMMO Digital — Agence digitale',
    description: 'IMMO Digital transforme vos idées en expériences digitales cohérentes, créatives et performantes : identité, communication et développement de solutions numériques.',
    ogLocale: 'fr_FR',
  },
  a11y: {
    skip: 'Aller au contenu', home: 'IMMO Digital — accueil', mainNav: 'Navigation principale', mobileNav: 'Navigation mobile',
    openMenu: 'Ouvrir le menu', closeMenu: 'Fermer le menu', themeDark: 'Activer le mode sombre', themeLight: 'Activer le mode clair', language: 'Langue',
    showPassword: 'Afficher le mot de passe', hidePassword: 'Masquer le mot de passe',
  },
  nav: { solutions: 'Solutions', about: 'À propos', reco: 'Recommandations', resources: 'Ressources', contact: 'Contact', cta: 'Lancer un projet' },
  domains: { ia: 'IA & Automatisation', web: 'Web & Mobile', infra: 'Infra & Sécurité' },
  footer: {
    slogan: 'Libérez le potentiel de vos idées', solutions: 'Solutions', company: 'Entreprise', resources: 'Ressources', contact: 'Contact',
    address: ['Douala, Yassa', 'À côté de Eneo'], insights: 'Tech Insights', client: 'Espace client', whatsapp: 'Écrire sur WhatsApp', rights: 'Tous droits réservés.', legal: 'Informations légales', legalNotice: 'Mentions légales', privacy: 'Confidentialité',
  },
  hero: {
    eyebrow: 'Libérez le potentiel de vos idées',
    title: 'Transformer vos idées en expériences digitales.',
    text: 'IMMO est une agence digitale : création, communication et développement de solutions numériques pour les entreprises, les marques et les entrepreneurs.',
  },
  prompt: { label: 'Posez une question à l’agent IMMO', placeholder: 'Décrivez votre idée à IMMO…', send: 'Envoyer' },
  symbol: { alt: 'Animation 3D : des points se rassemblent pour former le symbole IMMO' },
  statement: {
    aria: 'Notre conviction',
    text: 'Nous ne créons pas simplement du digital. Nous construisons des identités, des expériences et des stratégies qui donnent aux marques les moyens de se démarquer et d’évoluer.',
  },
  services: {
    title: 'Ce que nous faisons', all: 'Toutes nos solutions',
    rows: [
      { title: 'IA & Data', text: 'Automatiser vos tâches répétitives et exploiter vos données.' },
      { title: 'Développement Fullstack', text: 'Des applications web et mobiles rapides, sécurisées, sur mesure.' },
      { title: 'Direction Artistique & UI/UX', text: 'Une identité et des interfaces cohérentes, pensées pour vos utilisateurs.' },
      { title: 'Infra & Sécurité', text: 'Continuité de service, données protégées, haute disponibilité.' },
    ],
  },
  pillars: {
    title: 'Quatre piliers, une même exigence.',
    items: [
      { title: 'Créativité', text: 'Imaginer des concepts originaux et des identités visuelles fortes pour créer une vraie différence.' },
      { title: 'Innovation', text: 'Explorer les nouvelles tendances et technologies pour offrir des solutions modernes et évolutives.' },
      { title: 'Performance', text: 'Chaque création vise à améliorer la visibilité, renforcer l’image de marque et développer l’activité.' },
      { title: 'Proximité', text: 'Chaque client est un véritable partenaire : l’écoute et l’échange sont au cœur de chaque projet.' },
    ],
  },
  diagnostic: {
    title: 'Un premier regard sur votre projet.',
    text: 'Entrez le nom ou l’adresse de votre entreprise : notre bilan IA vous indique par où commencer.',
    label: 'Nom ou adresse web de votre entreprise', placeholder: 'Entrez le nom ou l’URL de votre entreprise…',
    button: 'Générer mon bilan IA', loading: 'Analyse en cours', reportFor: 'Bilan IA pour {name}',
    cta: 'Planifier un cadrage de 30 minutes', unavailable: 'Le bilan est indisponible pour le moment.', errorGeneric: 'Une erreur est survenue. Réessayez.',
  },
  terminal: {
    title: 'Posez vos questions à l’agent IMMO.',
    text: 'Faisabilité technique, estimation, méthode, rendez-vous : l’agent répond uniquement sur IMMO Digital.',
    role: 'Agent d’ingénierie numérique', logLabel: 'Conversation avec l’agent IMMO', label: 'Votre question pour l’agent IMMO',
    placeholder: 'Votre question sur IMMO Digital…', send: 'Envoyer la question', thinking: 'L’agent rédige sa réponse',
    connError: 'La connexion à l’agent a échoué. Réessayez dans un instant ou écrivez-nous depuis la page Contact.',
    greeting: 'Bonjour, je suis IMMO. Je réponds à vos questions sur IMMO Digital : nos pôles, notre méthode, la faisabilité de votre projet et la prise de rendez-vous.',
  },
  portal: {
    title: 'Suivez votre projet en temps réel',
    text: 'Votre espace privé réunit l’avancement, les livrables, les factures et le support de l’équipe infrastructure.',
    button: 'Accéder à votre espace privé', tablist: 'Modules du portail client', fictive: 'Aperçu avec des données fictives.',
    tabs: ['Suivi de projet', 'Livrables', 'Factures', 'Support'],
    cols: [
      { name: 'À faire', cards: ['Rédaction des API clients'] },
      { name: 'En cours', cards: ['Workflow n8n de relance', 'Intégration du paiement'] },
      { name: 'Validé', cards: ['Maquettes UI/UX', 'Modèle de données'] },
    ],
    rows: {
      deliverables: [['Dépôt GitHub privé', 'Disponible'], ['Documentation technique', 'Disponible'], ['Maquettes Figma', 'En préparation']],
      invoices: [['Devis — phase 1', 'Signé'], ['Facture — acompte', 'Payée'], ['Facture — jalon 2', 'En attente']],
      support: [['Latence sur la préproduction', 'Résolu'], ['Ajout d’un compte administrateur', 'En cours']],
    },
  },
  cta: { title: 'Parlons de votre idée.', text: 'Trente minutes avec un associé IMMO pour cadrer votre projet. Sans engagement.', primary: 'Lancer un projet', secondary: 'Voir nos solutions' },
  notFound: { title: 'Page introuvable', text: 'Cette page n’existe pas ou a été déplacée.', back: 'Retour à l’accueil' },
  legal: { formNote: 'Vos informations servent uniquement à traiter votre demande. En savoir plus :', privacyLink: 'politique de confidentialité' },
  error: { title: 'Un problème est survenu', text: 'Nous n’avons pas pu afficher cette page. Réessayez dans un instant.', retry: 'Réessayer' },
  api: { diagnosticInvalid: 'Saisissez le nom ou l’adresse de votre entreprise.', chatInvalid: 'Requête invalide.', rateLimited: 'Trop de questions en peu de temps. Réessayez dans quelques minutes ou écrivez-nous depuis la page Contact.' },
  agent: {
    quick: ['Comment automatiser mes processus d’entreprise ?', 'Quelle architecture pour mon application mobile ?', 'Quels sont vos délais moyens de livraison ?'],
    offTopic: 'Je suis IMMO, l’agent dédié d’IMMO Digital. Mon rôle est exclusivement de vous accompagner dans la numérisation et la transformation digitale de votre entreprise. Comment puis-je vous aider sur vos projets Web, IA, Design ou Infrastructure ?',
    next: 'Souhaitez-vous planifier une session de cadrage de 30 minutes avec notre associé référent ?',
    local: {
      automation: 'Nous partons de vos tâches répétitives (saisies, relances, facturation, suivi client) et les relions dans des workflows n8n, avec un agent IA métier pour les demandes récurrentes.\n\n- Audit de vos flux de travail\n- Workflows n8n et intégrations CRM/emails\n- Agent conversationnel adossé à vos données',
      app: 'Pour une application mobile, nous privilégions une PWA Next.js ou une app React Native selon vos usages hors ligne et vos besoins d’accès aux capteurs.\n\n- Maquettes Figma et design system\n- API scalable et base PostgreSQL/Supabase\n- Déploiement sécurisé et évolutif',
      delay: 'Les délais dépendent du périmètre : ils sont fixés à l’issue du cadrage, étape par étape (audit, architecture, ingénierie, déploiement). Nous ne promettons pas de date avant d’avoir compris votre besoin.',
      price: 'Nous n’affichons pas de prix fixe : chaque projet fait l’objet d’un cadrage sur mesure, puis d’un devis détaillé.',
      meeting: 'Vous pouvez réserver une session de 30 minutes depuis la page Contact : choisissez votre besoin, votre échéance, puis un créneau avec l’associé du pôle concerné.',
      about: 'IMMO Digital réunit des associés complémentaires : IA & Data, Fullstack, Direction Artistique & UI/UX, Infra & Sécurité. Pas de sous-traitance : vous échangez directement avec ceux qui construisent votre projet.',
    },
  },
  report: {
    axes: [
      { pole: 'IA & Automatisation', title: 'Workflows n8n à fort gain de temps', detail: 'Pour {name} : relances, saisies, facturation et suivi client peuvent être reliés dans des workflows n8n, avec un agent IA métier pour les demandes récurrentes.' },
      { pole: 'Web & Mobile', title: 'Application PWA rapide et installable', detail: 'Une PWA Next.js donne à vos clients un accès direct sur mobile, sans passer par un store, avec un budget de performance fixé dès le cadrage.' },
      { pole: 'Infra & Sécurité', title: 'Haute disponibilité et protection des données', detail: 'Conteneurisation, sauvegardes, CDN et en-têtes de sécurité pour éviter les coupures et protéger les données sensibles.' },
    ],
  },
};
export type Dict = typeof fr;
