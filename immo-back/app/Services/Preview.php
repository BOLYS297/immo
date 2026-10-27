<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Jetons d'aperçu : l'admin crée un jeton (aléatoire, 2 h) qui désigne ce qu'il faut afficher ; le front Next
 * l'échange contre le contenu via GET /api/v1/preview/{jeton} (clé du front exigée) et l'affiche à /apercu.
 * Contenu : ['kind' => 'page', 'page_id', 'revision_id' (null = brouillon), 'overrides' => [section_id => …]]
 *        ou ['kind' => 'post', 'post_id'].
 */
class Preview
{
    private const TTL_MINUTES = 120;

    public static function create(array $payload): string
    {
        $token = Str::random(48);
        Cache::put(static::key($token), $payload, now()->addMinutes(self::TTL_MINUTES));

        return $token;
    }

    public static function get(string $token): ?array
    {
        return preg_match('/^[A-Za-z0-9]{48}$/', $token) ? Cache::get(static::key($token)) : null;
    }

    /** Remplace le contenu d'aperçu d'une section (édition en direct) ; false si le jeton a expiré. */
    public static function override(string $token, int $sectionId, array $section): bool
    {
        $payload = static::get($token);
        if (! $payload || $payload['kind'] !== 'page') {
            return false;
        }
        $payload['overrides'][$sectionId] = $section;
        Cache::put(static::key($token), $payload, now()->addMinutes(self::TTL_MINUTES));

        return true;
    }

    /** Adresse de l'aperçu sur le front ; l'ancre fait défiler jusqu'à une section. */
    public static function url(string $token, ?string $anchor = null): string
    {
        return rtrim(config('services.front.url'), '/').'/apercu?jeton='.$token.($anchor ? "#{$anchor}" : '');
    }

    private static function key(string $token): string
    {
        return "preview:{$token}";
    }
}
