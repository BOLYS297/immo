<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Garde-fou : les tests vident leur base (RefreshDatabase). Une configuration mise en cache
     * (php artisan optimize / config:cache) ignore phpunit.xml et pointerait vers la vraie base :
     * on refuse alors de démarrer, avant la moindre requête.
     */
    public function createApplication()
    {
        if (is_file(dirname(__DIR__).'/bootstrap/cache/config.php')) {
            throw new RuntimeException('Configuration en cache détectée (bootstrap/cache/config.php) : lancez « php artisan config:clear » avant les tests, sinon ils videraient la vraie base de données.');
        }

        $app = parent::createApplication();

        $database = (string) $app['config']->get('database.connections.'.$app['config']->get('database.default').'.database');
        if (! str_ends_with($database, '_testing')) {
            throw new RuntimeException("Les tests doivent viser une base « *_testing » (base actuelle : « {$database} »). Arrêt pour protéger les données.");
        }

        return $app;
    }
}
