<?php

namespace App\Providers;

use App\Models\Post;
use App\Models\User;
use App\Services\Activity;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Translatable\Translatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Administration en français : dates relatives (« il y a 5 minutes ») et noms de mois.
        Carbon::setLocale('fr');

        // Texte absent dans une langue : version française (langue par défaut du site), pas la langue de l'application.
        app(Translatable::class)->fallback(fallbackLocale: config('cms.default_locale'));

        // Admin : {client} ne résout que des comptes clients du portail, jamais un administrateur ;
        // {member} que des comptes de l'équipe, jamais un client.
        Route::bind('client', fn (string $value) => User::clients()->findOrFail($value));
        Route::bind('member', fn (string $value) => User::staff()->findOrFail($value));

        // Journal d'activité : connexions de l'équipe et tentatives échouées sur l'administration.
        Event::listen(Login::class, fn (Login $e) => $e->user instanceof User && $e->user->isStaff() ? Activity::record('auth.login', $e->user, user: $e->user) : null);
        Event::listen(Logout::class, fn (Logout $e) => $e->user instanceof User && $e->user->isStaff() ? Activity::record('auth.logout', $e->user, user: $e->user) : null);
        Event::listen(Failed::class, function (Failed $e) {
            if (request()->is('admin/*')) {
                Activity::record('auth.failed', null, ['email' => mb_substr((string) ($e->credentials['email'] ?? ''), 0, 191)]);
            }
        });

        // Permissions de l'administration (config/admin.php) : l'administrateur les a toutes.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);
        foreach (array_keys(config('admin.permissions')) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }
        // Un article : tous pour qui gère les articles ; sinon seulement ses propres brouillons
        // (un article publié n'est plus modifiable que par un éditeur).
        Gate::define('posts.edit', fn (User $user, Post $post) => $user->hasPermission('posts.manage')
            || ($user->hasPermission('posts.write') && $post->user_id === $user->id && ! $post->is_published));

        // Formulaires du site : les requêtes arrivent toutes du serveur Next, qui transmet l'IP du visiteur
        // (X-Client-Ip). La route exige la clé du front : cet en-tête ne peut pas être forgé par un tiers.
        RateLimiter::for('front-forms', function (Request $request) {
            $ip = filter_var($request->header('X-Client-Ip'), FILTER_VALIDATE_IP) ?: $request->ip();

            return [
                Limit::perMinute(10)->by('form:'.$ip),
                Limit::perMinute(300)->by('form:all'),
            ];
        });

        // Portail client : par compte connecté.
        RateLimiter::for('portal', fn (Request $request) => Limit::perMinute(120)->by('portal:'.$request->user()?->id));
        RateLimiter::for('portal-tickets', fn (Request $request) => Limit::perMinutes(10, 10)->by('tickets:'.$request->user()?->id));
    }
}
