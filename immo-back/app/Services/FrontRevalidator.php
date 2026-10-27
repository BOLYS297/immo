<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use function Illuminate\Support\defer;

/**
 * Prévient le front Next.js qu'un contenu a changé, pour qu'il vide son cache
 * (revalidateTag) sans attendre l'expiration. Appel envoyé après la réponse HTTP :
 * un front indisponible ne bloque ni ne fait échouer l'enregistrement dans l'admin.
 */
class FrontRevalidator
{
    public static function tags(string ...$tags): void
    {
        $secret = config('services.front.revalidate_secret');

        if (! $secret || $tags === []) {
            return;
        }

        defer(function () use ($secret, $tags) {
            try {
                Http::timeout(5)
                    ->withHeaders(['x-revalidate-secret' => $secret])
                    ->post(config('services.front.url').'/api/revalidate', ['tags' => array_values(array_unique($tags))])
                    ->throw();
            } catch (\Throwable $e) {
                Log::warning('[cms] Revalidation du front impossible : '.$e->getMessage(), ['tags' => $tags]);
            }
        });
    }
}
