import type { Dict } from './fr';
import { pagesEn } from './pages-en';

export const en: Dict = {
  pages: pagesEn,
  meta: {
    title: 'IMMO Digital — Digital agency',
    description: 'IMMO Digital turns your ideas into coherent, creative and high-performing digital experiences: identity, communication and digital solutions development.',
    ogLocale: 'en_US',
  },
  a11y: {
    skip: 'Skip to content', home: 'IMMO Digital — home', mainNav: 'Main navigation', mobileNav: 'Mobile navigation',
    openMenu: 'Open menu', closeMenu: 'Close menu', themeDark: 'Switch to dark mode', themeLight: 'Switch to light mode', language: 'Language',
    showPassword: 'Show password', hidePassword: 'Hide password',
  },
  nav: { solutions: 'Solutions', about: 'About', reco: 'Testimonials', resources: 'Resources', contact: 'Contact', cta: 'Start a project' },
  domains: { ia: 'AI & Automation', web: 'Web & Mobile', infra: 'Infra & Security' },
  footer: {
    slogan: 'Unlock the potential of your ideas', solutions: 'Solutions', company: 'Company', resources: 'Resources', contact: 'Contact',
    address: ['Douala, Yassa', 'Next to Eneo'], insights: 'Tech Insights', client: 'Client portal', whatsapp: 'Message us on WhatsApp', rights: 'All rights reserved.', legal: 'Legal information', legalNotice: 'Legal notice', privacy: 'Privacy',
  },
  hero: {
    eyebrow: 'Unlock the potential of your ideas',
    title: 'Turn your ideas into digital experiences.',
    text: 'IMMO is a digital agency: creation, communication and development of digital solutions for companies, brands and entrepreneurs.',
  },
  prompt: { label: 'Ask the IMMO agent a question', placeholder: 'Describe your idea to IMMO…', send: 'Send' },
  symbol: { alt: '3D animation: points gather to form the IMMO symbol' },
  statement: {
    aria: 'Our conviction',
    text: 'We don’t simply create digital. We build identities, experiences and strategies that give brands the means to stand out and grow.',
  },
  services: {
    title: 'What we do', all: 'All our solutions',
    rows: [
      { title: 'AI & Data', text: 'Automate your repetitive tasks and make the most of your data.' },
      { title: 'Fullstack Development', text: 'Fast, secure, tailor-made web and mobile applications.' },
      { title: 'Art Direction & UI/UX', text: 'A coherent identity and interfaces designed around your users.' },
      { title: 'Infra & Security', text: 'Service continuity, protected data, high availability.' },
    ],
  },
  pillars: {
    title: 'Four pillars, one standard.',
    items: [
      { title: 'Creativity', text: 'Imagining original concepts and strong visual identities to make a real difference.' },
      { title: 'Innovation', text: 'Exploring new trends and technologies to deliver modern, scalable solutions.' },
      { title: 'Performance', text: 'Every creation aims to improve visibility, strengthen brand image and grow your business.' },
      { title: 'Proximity', text: 'Every client is a true partner: listening and dialogue are at the heart of every project.' },
    ],
  },
  diagnostic: {
    title: 'A first look at your project.',
    text: 'Enter your company’s name or website: our AI assessment shows you where to start.',
    label: 'Your company’s name or website', placeholder: 'Enter your company’s name or URL…',
    button: 'Generate my AI assessment', loading: 'Analysing', reportFor: 'AI assessment for {name}',
    cta: 'Book a 30-minute scoping call', unavailable: 'The assessment is unavailable right now.', errorGeneric: 'Something went wrong. Please try again.',
  },
  terminal: {
    title: 'Ask the IMMO agent.',
    text: 'Technical feasibility, estimates, method, appointments: the agent only answers questions about IMMO Digital.',
    role: 'Digital engineering agent', logLabel: 'Conversation with the IMMO agent', label: 'Your question for the IMMO agent',
    placeholder: 'Your question about IMMO Digital…', send: 'Send question', thinking: 'The agent is writing its answer',
    connError: 'The connection to the agent failed. Try again in a moment or write to us from the Contact page.',
    greeting: 'Hello, I’m IMMO. I answer your questions about IMMO Digital: our teams, our method, the feasibility of your project and booking a meeting.',
  },
  portal: {
    title: 'Follow your project in real time',
    text: 'Your private space brings together progress, deliverables, invoices and support from the infrastructure team.',
    button: 'Access your private space', tablist: 'Client portal modules', fictive: 'Preview with sample data.',
    tabs: ['Project tracking', 'Deliverables', 'Invoices', 'Support'],
    cols: [
      { name: 'To do', cards: ['Client API documentation'] },
      { name: 'In progress', cards: ['n8n follow-up workflow', 'Payment integration'] },
      { name: 'Done', cards: ['UI/UX mockups', 'Data model'] },
    ],
    rows: {
      deliverables: [['Private GitHub repository', 'Available'], ['Technical documentation', 'Available'], ['Figma mockups', 'In preparation']],
      invoices: [['Quote — phase 1', 'Signed'], ['Invoice — deposit', 'Paid'], ['Invoice — milestone 2', 'Pending']],
      support: [['Staging latency', 'Resolved'], ['Add an administrator account', 'In progress']],
    },
  },
  cta: { title: 'Let’s talk about your idea.', text: 'Thirty minutes with an IMMO partner to scope your project. No commitment.', primary: 'Start a project', secondary: 'See our solutions' },
  notFound: { title: 'Page not found', text: 'This page doesn’t exist or has been moved.', back: 'Back to home' },
  legal: { formNote: 'Your details are only used to handle your request. Learn more:', privacyLink: 'privacy policy' },
  error: { title: 'Something went wrong', text: 'We couldn’t display this page. Please try again in a moment.', retry: 'Try again' },
  api: { diagnosticInvalid: 'Enter your company’s name or website.', chatInvalid: 'Invalid request.', rateLimited: 'Too many questions in a short time. Try again in a few minutes or write to us from the Contact page.' },
  agent: {
    quick: ['How can I automate my business processes?', 'What architecture for my mobile app?', 'What are your average delivery times?'],
    offTopic: 'I’m IMMO, IMMO Digital’s dedicated agent. My role is solely to support you with the digitalisation and digital transformation of your business. How can I help with your Web, AI, Design or Infrastructure projects?',
    next: 'Would you like to book a 30-minute scoping session with our lead partner?',
    local: {
      automation: 'We start from your repetitive tasks (data entry, follow-ups, invoicing, customer tracking) and connect them in n8n workflows, with an AI agent handling recurring requests.\n\n- Audit of your workflows\n- n8n workflows and CRM/email integrations\n- A conversational agent connected to your data',
      app: 'For a mobile app, we favour a Next.js PWA or a React Native app depending on your offline needs and device access requirements.\n\n- Figma mockups and design system\n- Scalable API and PostgreSQL/Supabase database\n- Secure, scalable deployment',
      delay: 'Timelines depend on scope: they are set after scoping, step by step (audit, architecture, engineering, deployment). We don’t promise a date before understanding your needs.',
      price: 'We don’t display fixed prices: each project is scoped individually, then quoted in detail.',
      meeting: 'You can book a 30-minute session from the Contact page: choose your need, your timeline, then a slot with the partner of the relevant team.',
      about: 'IMMO Digital brings together complementary partners: AI & Data, Fullstack, Art Direction & UI/UX, Infra & Security. No subcontracting: you speak directly with the people building your project.',
    },
  },
  report: {
    axes: [
      { pole: 'AI & Automation', title: 'n8n workflows with major time savings', detail: 'For {name}: follow-ups, data entry, invoicing and customer tracking can be connected in n8n workflows, with an AI agent handling recurring requests.' },
      { pole: 'Web & Mobile', title: 'A fast, installable PWA', detail: 'A Next.js PWA gives your customers direct mobile access without an app store, with a performance budget set from the scoping stage.' },
      { pole: 'Infra & Security', title: 'High availability and data protection', detail: 'Containerisation, backups, CDN and security headers to prevent outages and protect sensitive data.' },
    ],
  },
};
