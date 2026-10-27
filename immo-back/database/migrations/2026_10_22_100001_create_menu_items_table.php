<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('menu', 30)->index(); // emplacement : clé de config('cms.menus')
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->json('label');
            $table->string('url')->nullable(); // null pour une colonne du pied de page
            $table->string('display', 10)->default('all');
            $table->boolean('new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $this->seedCurrentFrontMenus();
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }

    /** Liens affichés jusqu'ici en dur par le front (Navbar, MobileMenu, Footer) : le site reste identique. */
    private function seedCurrentFrontMenus(): void
    {
        $insert = function (string $menu, int $order, array $label, ?string $url, ?int $parent = null, string $display = 'all'): int {
            return DB::table('menu_items')->insertGetId([
                'menu' => $menu, 'parent_id' => $parent, 'label' => json_encode($label), 'url' => $url,
                'display' => $display, 'new_tab' => false, 'sort_order' => $order, 'created_at' => now(), 'updated_at' => now(),
            ]);
        };

        foreach ([
            [['fr' => 'Solutions', 'en' => 'Solutions'], '/services', 'all'],
            [['fr' => 'À propos', 'en' => 'About'], '/a-propos', 'all'],
            [['fr' => 'Recommandations', 'en' => 'Testimonials'], '/recommandations', 'all'],
            [['fr' => 'Ressources', 'en' => 'Resources'], '/tech-insights', 'all'],
            [['fr' => 'Contact', 'en' => 'Contact'], '/contact', 'mobile'],
            [['fr' => 'Espace client', 'en' => 'Client portal'], '/dashboard', 'mobile'],
        ] as $i => [$label, $url, $display]) {
            $insert('header', $i, $label, $url, null, $display);
        }

        foreach ([
            [['fr' => 'Solutions', 'en' => 'Solutions'], [
                [['fr' => 'IA & Automatisation', 'en' => 'AI & Automation'], '/services?domaine=ia#chiffrage'],
                [['fr' => 'Web & Mobile', 'en' => 'Web & Mobile'], '/services?domaine=web#chiffrage'],
                [['fr' => 'Infra & Sécurité', 'en' => 'Infra & Security'], '/services?domaine=infra#chiffrage'],
            ]],
            [['fr' => 'Entreprise', 'en' => 'Company'], [
                [['fr' => 'À propos', 'en' => 'About'], '/a-propos'],
                [['fr' => 'Recommandations', 'en' => 'Testimonials'], '/recommandations'],
                [['fr' => 'Contact', 'en' => 'Contact'], '/contact'],
            ]],
            [['fr' => 'Ressources', 'en' => 'Resources'], [
                [['fr' => 'Tech Insights', 'en' => 'Tech Insights'], '/tech-insights'],
                [['fr' => 'Espace client', 'en' => 'Client portal'], '/dashboard'],
            ]],
        ] as $i => [$label, $links]) {
            $column = $insert('footer', $i, $label, null);
            foreach ($links as $j => [$linkLabel, $url]) {
                $insert('footer', $j, $linkLabel, $url, $column);
            }
        }

        $insert('legal', 0, ['fr' => 'Mentions légales', 'en' => 'Legal notice'], '/mentions-legales');
        $insert('legal', 1, ['fr' => 'Confidentialité', 'en' => 'Privacy'], '/confidentialite');
    }
};
