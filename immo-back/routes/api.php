<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PortalController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PreviewController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\StructuredContentController;
use App\Http\Controllers\Api\TestimonialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API consommée par le serveur du front Next.js (immo-front)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    // Contenu public, en lecture
    Route::middleware('throttle:120,1')->group(function () {
        Route::get('/site', [SiteController::class, 'show'])->name('api.site.show');
        Route::get('/pages', [PageController::class, 'index'])->name('api.pages.index');
        Route::get('/pages/{slug}', [PageController::class, 'show'])->name('api.pages.show');
        Route::get('/posts', [PostController::class, 'index'])->name('api.posts.index');
        Route::get('/posts/{slug}', [PostController::class, 'show'])->name('api.posts.show');
        Route::get('/testimonials', [TestimonialController::class, 'index'])->name('api.testimonials.index');
        Route::get('/services', [StructuredContentController::class, 'services'])->name('api.services.index');
        Route::get('/projects', [StructuredContentController::class, 'projects'])->name('api.projects.index');
        Route::get('/faqs', [StructuredContentController::class, 'faqs'])->name('api.faqs.index');
    });

    // Formulaires du site, transmis par le serveur Next (clé X-Front-Key)
    Route::middleware(['front.key', 'throttle:front-forms'])->group(function () {
        Route::post('/quotes', [QuoteController::class, 'store'])->name('api.quotes.store');
        Route::post('/messages', [MessageController::class, 'store'])->name('api.messages.store');
        Route::get('/bookings/taken', [BookingController::class, 'taken'])->name('api.bookings.taken');
        Route::post('/bookings', [BookingController::class, 'store'])->name('api.bookings.store');

        // Portail client : connexion
        Route::post('/portal/login', [PortalController::class, 'login'])->name('api.portal.login');
        Route::post('/portal/preview', [PortalController::class, 'preview'])->name('api.portal.preview');
    });

    // Aperçu de l'admin (brouillons, révisions, articles non publiés) : jeton créé par Laravel.
    // Limite plus large que les formulaires : l'aperçu en direct recharge la page à chaque modification.
    Route::get('/preview/{token}', [PreviewController::class, 'show'])->middleware(['front.key', 'throttle:120,1'])->name('api.preview.show');

    // Portail client : routes du client connecté (jeton Sanctum gardé par le serveur Next)
    Route::middleware(['front.key', 'auth:sanctum', 'throttle:portal'])->prefix('portal')->name('api.portal.')->group(function () {
        Route::get('/me', [PortalController::class, 'me'])->name('me');
        Route::post('/logout', [PortalController::class, 'logout'])->name('logout');
        Route::post('/documents/{item}/sign', [PortalController::class, 'sign'])->name('sign');
        Route::post('/tickets', [PortalController::class, 'ticket'])->middleware('throttle:portal-tickets')->name('tickets');
        Route::put('/password', [PortalController::class, 'password'])->name('password');
    });
});
