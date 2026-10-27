<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Pages système (config cms.system_pages) reprenant à l'identique les textes actuels du front (src/lib/dictionaries) :
 * l'accueil ci-dessous, les autres depuis data/system-pages.json (généré par immo-front/scripts/export-system-pages.mjs).
 * Une page qui existe déjà n'est jamais modifiée : les changements faits dans l'admin ne sont pas écrasés.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedHome();

        $pages = json_decode(file_get_contents(__DIR__.'/data/system-pages.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($pages as $slug => $definition) {
            if (Page::where('slug', $slug)->exists()) {
                continue;
            }
            $page = Page::create([
                'slug' => $slug,
                'title' => $definition['title'],
                'meta_title' => $definition['meta_title'],
                'meta_description' => $definition['meta_description'],
                'is_published' => true,
            ]);
            foreach ($definition['sections'] as $order => $section) {
                $page->sections()->create([
                    'type' => $section['type'],
                    'content' => $section['content'],
                    'settings' => $section['settings'],
                    'is_visible' => true,
                    'sort_order' => $order,
                ]);
            }
            $page->publish(); // première version en ligne
        }
    }

    private function seedHome(): void
    {
        if (Page::where('slug', 'home')->exists()) {
            return;
        }

        $page = Page::create([
            'slug' => 'home',
            'title' => ['fr' => 'Accueil', 'en' => 'Home'],
            'meta_title' => ['fr' => 'IMMO Digital — Agence digitale', 'en' => 'IMMO Digital — Digital agency'],
            'meta_description' => [
                'fr' => 'IMMO Digital transforme vos idées en expériences digitales cohérentes, créatives et performantes : identité, communication et développement de solutions numériques.',
                'en' => 'IMMO Digital turns your ideas into coherent, creative and high-performing digital experiences: identity, communication and digital solutions development.',
            ],
            'is_published' => true,
        ]);

        $sections = [
            'hero' => [
                'fr' => [
                    'eyebrow' => 'Libérez le potentiel de vos idées',
                    'title' => 'Transformer vos idées en expériences digitales.',
                    'text' => 'IMMO est une agence digitale : création, communication et développement de solutions numériques pour les entreprises, les marques et les entrepreneurs.',
                ],
                'en' => [
                    'eyebrow' => 'Unlock the potential of your ideas',
                    'title' => 'Turn your ideas into digital experiences.',
                    'text' => 'IMMO is a digital agency: creation, communication and development of digital solutions for companies, brands and entrepreneurs.',
                ],
            ],
            'statement' => [
                'fr' => ['text' => 'Nous ne créons pas simplement du digital. Nous construisons des identités, des expériences et des stratégies qui donnent aux marques les moyens de se démarquer et d’évoluer.'],
                'en' => ['text' => 'We don’t simply create digital. We build identities, experiences and strategies that give brands the means to stand out and grow.'],
            ],
            'services' => [
                'fr' => ['title' => 'Ce que nous faisons', 'all' => 'Toutes nos solutions', 'rows' => [
                    ['title' => 'IA & Data', 'text' => 'Automatiser vos tâches répétitives et exploiter vos données.', 'href' => '/services?domaine=ia#chiffrage'],
                    ['title' => 'Développement Fullstack', 'text' => 'Des applications web et mobiles rapides, sécurisées, sur mesure.', 'href' => '/services?domaine=web#chiffrage'],
                    ['title' => 'Direction Artistique & UI/UX', 'text' => 'Une identité et des interfaces cohérentes, pensées pour vos utilisateurs.', 'href' => '/services?domaine=web#chiffrage'],
                    ['title' => 'Infra & Sécurité', 'text' => 'Continuité de service, données protégées, haute disponibilité.', 'href' => '/services?domaine=infra#chiffrage'],
                ]],
                'en' => ['title' => 'What we do', 'all' => 'All our solutions', 'rows' => [
                    ['title' => 'AI & Data', 'text' => 'Automate your repetitive tasks and make the most of your data.', 'href' => '/services?domaine=ia#chiffrage'],
                    ['title' => 'Fullstack Development', 'text' => 'Fast, secure, tailor-made web and mobile applications.', 'href' => '/services?domaine=web#chiffrage'],
                    ['title' => 'Art Direction & UI/UX', 'text' => 'A coherent identity and interfaces designed around your users.', 'href' => '/services?domaine=web#chiffrage'],
                    ['title' => 'Infra & Security', 'text' => 'Service continuity, protected data, high availability.', 'href' => '/services?domaine=infra#chiffrage'],
                ]],
            ],
            'pillars' => [
                'fr' => ['title' => 'Quatre piliers, une même exigence.', 'items' => [
                    ['title' => 'Créativité', 'text' => 'Imaginer des concepts originaux et des identités visuelles fortes pour créer une vraie différence.'],
                    ['title' => 'Innovation', 'text' => 'Explorer les nouvelles tendances et technologies pour offrir des solutions modernes et évolutives.'],
                    ['title' => 'Performance', 'text' => 'Chaque création vise à améliorer la visibilité, renforcer l’image de marque et développer l’activité.'],
                    ['title' => 'Proximité', 'text' => 'Chaque client est un véritable partenaire : l’écoute et l’échange sont au cœur de chaque projet.'],
                ]],
                'en' => ['title' => 'Four pillars, one standard.', 'items' => [
                    ['title' => 'Creativity', 'text' => 'Imagining original concepts and strong visual identities to make a real difference.'],
                    ['title' => 'Innovation', 'text' => 'Exploring new trends and technologies to deliver modern, scalable solutions.'],
                    ['title' => 'Performance', 'text' => 'Every creation aims to improve visibility, strengthen brand image and grow your business.'],
                    ['title' => 'Proximity', 'text' => 'Every client is a true partner: listening and dialogue are at the heart of every project.'],
                ]],
            ],
            'diagnostic' => [
                'fr' => ['title' => 'Un premier regard sur votre projet.', 'text' => 'Entrez le nom ou l’adresse de votre entreprise : notre bilan IA vous indique par où commencer.'],
                'en' => ['title' => 'A first look at your project.', 'text' => 'Enter your company’s name or website: our AI assessment shows you where to start.'],
            ],
            'terminal' => [
                'fr' => ['title' => 'Posez vos questions à l’agent IMMO.', 'text' => 'Faisabilité technique, estimation, méthode, rendez-vous : l’agent répond uniquement sur IMMO Digital.'],
                'en' => ['title' => 'Ask the IMMO agent.', 'text' => 'Technical feasibility, estimates, method, appointments: the agent only answers questions about IMMO Digital.'],
            ],
            'portal_preview' => [
                'fr' => ['title' => 'Suivez votre projet en temps réel', 'text' => 'Votre espace privé réunit l’avancement, les livrables, les factures et le support de l’équipe infrastructure.', 'button' => 'Accéder à votre espace privé'],
                'en' => ['title' => 'Follow your project in real time', 'text' => 'Your private space brings together progress, deliverables, invoices and support from the infrastructure team.', 'button' => 'Access your private space'],
            ],
            'cta_band' => [
                'fr' => ['title' => 'Parlons de votre idée.', 'text' => 'Trente minutes avec un associé IMMO pour cadrer votre projet. Sans engagement.', 'primary' => 'Lancer un projet', 'primary_href' => '/contact', 'secondary' => 'Voir nos solutions', 'secondary_href' => '/services'],
                'en' => ['title' => 'Let’s talk about your idea.', 'text' => 'Thirty minutes with an IMMO partner to scope your project. No commitment.', 'primary' => 'Start a project', 'primary_href' => '/contact', 'secondary' => 'See our solutions', 'secondary_href' => '/services'],
            ],
        ];

        $order = 0;
        foreach ($sections as $type => $content) {
            $page->sections()->create(['type' => $type, 'content' => $content, 'is_visible' => true, 'sort_order' => $order++]);
        }
        $page->publish();
    }
}
