<?php

namespace Database\Seeders;

use App\Models\Prospect;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Administrateur initial : identifiants lus dans .env (ADMIN_EMAIL, ADMIN_PASSWORD), jamais écrits dans le code.
        //    Sans ADMIN_PASSWORD, un mot de passe aléatoire est créé et affiché une seule fois.
        $email = env('ADMIN_EMAIL', 'admin@agence-digitale.com');
        $password = env('ADMIN_PASSWORD');
        $generated = blank($password);
        if ($generated) {
            $password = Str::password(20);
        }

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Directeur Technique',
                'password' => Hash::make($password),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
        if ($admin->wasRecentlyCreated && $generated) {
            $this->command?->warn("Administrateur créé : {$email} / {$password} (notez ce mot de passe, il ne sera plus affiché).");
        }

        // 2. Réglages du site : valeurs par défaut de config/cms.php (settings), modifiables dans l'admin.

        // 3. Services, réalisations, FAQ : aucun contenu fictif. Les trois services du site sont créés par la migration
        //    2026_10_22_140000 ; réalisations et questions se saisissent dans l'admin (accord écrit du client pour les cas clients).

        // 4. Demande de devis de démonstration (visible seulement dans l'admin)
        $prospectDemo = Prospect::firstOrCreate(
            ['email' => 'direction@acme-corp.fr'],
            [
                'last_name' => 'Dupont',
                'first_name' => 'Claire',
                'phone' => '+33 6 98 76 54 32',
                'company' => 'Acme Corporation',
            ]
        );

        QuoteRequest::firstOrCreate(
            ['reference' => 'DEV-202610-0001'],
            [
                'prospect_id' => $prospectDemo->id,
                'budget_range' => '5 000 € - 15 000 €',
                'timeline' => '1 à 2 mois',
                'description' => 'Nous souhaitons développer une plateforme web interne permettant de centraliser les rapports d\'intervention de nos 50 techniciens et d\'y adjoindre un module d\'analyse intelligente.',
                'status' => QuoteRequest::STATUS_NEW,
                'privacy_consent' => true,
            ]
        );


        // Contenus du front Next.js : pages et articles Tech Insights.
        // Aucun avis client n'est créé : un avis ne se publie qu'avec l'accord écrit d'un vrai client.
        $this->call([PageSeeder::class, ArticleSeeder::class]);
    }
}
