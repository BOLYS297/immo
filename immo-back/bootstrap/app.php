<?php

use App\Http\Middleware\EnsureAdminUser;
use App\Http\Middleware\EnsureFrontKey;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Production : Laravel derrière le frontal nginx (réseau Docker privé). Sans cela, HTTPS, hôte et IP du
        // visiteur seraient ceux du proxy (liens en http://, limite de connexions partagée par tous les visiteurs).
        $middleware->trustProxies(at: ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16']);
        $middleware->alias([
            'admin' => EnsureAdminUser::class,
            'front.key' => EnsureFrontKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
