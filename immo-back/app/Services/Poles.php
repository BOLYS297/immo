<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Support\Facades\Schema;

/**
 * Pôles de l'agence : chaque service publié (rubrique « Services ») est un pôle, désigné par son identifiant (slug).
 * Formulaire de chiffrage, prise de rendez-vous, avis clients et tâches du portail proposent ces pôles.
 * Tant qu'aucun service n'est publié, les trois pôles historiques (ia, web, infra) restent proposés ; leurs clés
 * restent lisibles dans les anciennes demandes.
 */
class Poles
{
    public const LEGACY = ['ia' => 'IA & Automatisation', 'web' => 'Web & Mobile', 'infra' => 'Infra & Sécurité'];

    /** @var array<string, array<string, string>> */
    private static array $memo = [];

    /**
     * Pôles proposés : identifiant => titre du service dans la langue demandée (repli : langue par défaut).
     *
     * @return array<string, string>
     */
    public static function options(?string $locale = null): array
    {
        $locale ??= config('cms.default_locale');

        return static::$memo[$locale] ??= (function () use ($locale) {
            $services = Schema::hasTable('services') ? Service::published()->orderBy('sort_order')->orderBy('id')->get() : collect();

            return $services->isEmpty()
                ? self::LEGACY
                : $services->mapWithKeys(fn (Service $s) => [$s->slug => $s->getTranslation('title', $locale) ?: $s->slug])->all();
        })();
    }

    /**
     * Clés acceptées par les formulaires : pôles proposés, plus les pôles historiques (pages en cache).
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_values(array_unique([...array_keys(static::options()), ...array_keys(self::LEGACY)]));
    }

    /** Libellé d'un pôle enregistré : titre du service (même dépublié), pôle historique, sinon la valeur telle quelle. */
    public static function label(?string $key, ?string $locale = null): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }
        $locale ??= config('cms.default_locale');
        if (isset(static::options($locale)[$key])) {
            return static::options($locale)[$key];
        }
        $service = Service::where('slug', $key)->first();

        return $service ? ($service->getTranslation('title', $locale) ?: $key) : (self::LEGACY[$key] ?? $key);
    }

    /**
     * Liste de choix dont les options sont les pôles (`'options' => 'poles'` dans config/cms.php ou config/portal.php,
     * résolue à l'usage : la configuration mise en cache ne lit pas la base). Option vide : `empty_label`.
     */
    public static function resolve(array $field): array
    {
        if (($field['options'] ?? null) === 'poles') {
            $field['options'] = ['' => $field['empty_label'] ?? '—'] + static::options();
        }

        return $field;
    }

    /** À appeler quand les services changent (enregistrement, tests). */
    public static function flush(): void
    {
        static::$memo = [];
    }
}
