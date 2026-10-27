<?php

use App\Http\Controllers\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PageSectionController as AdminPageSectionController;
use App\Http\Controllers\Admin\PortalItemController as AdminPortalItemController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\QuoteController as AdminQuoteController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Le site public est servi par le front Next.js (immo-front), qui lit le
| contenu via routes/api.php. Laravel ne sert que l'administration.
|--------------------------------------------------------------------------
*/
Route::redirect('/', '/admin');

/*
|--------------------------------------------------------------------------
| Authentication Routes for Administration
|--------------------------------------------------------------------------
*/
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1')->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Compatibility alias for standard auth redirect
Route::get('/login', fn () => redirect()->route('admin.login'))->name('login');

/*
|--------------------------------------------------------------------------
| Protected Administration Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['admin', 'auth.session'])->prefix('admin')->name('admin.')->group(function () {
    // Accessible à toute l'équipe ; chaque rubrique est réservée aux rôles qui en ont la permission (config/admin.php).
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/compte', [AdminAccountController::class, 'edit'])->name('account.edit');
    Route::put('/compte', [AdminAccountController::class, 'update'])->name('account.update');

    Route::middleware('can:leads.manage')->group(function () {
        // Devis
        Route::get('/devis', [AdminQuoteController::class, 'index'])->name('quotes.index');
        Route::get('/devis/{quote}', [AdminQuoteController::class, 'show'])->name('quotes.show');
        Route::patch('/devis/{quote}/status', [AdminQuoteController::class, 'updateStatus'])->name('quotes.status');
        Route::patch('/devis/{quote}/notes', [AdminQuoteController::class, 'updateNotes'])->name('quotes.notes');
        Route::get('/devis/{quote}/download', [AdminQuoteController::class, 'downloadAttachment'])->name('quotes.download');
        Route::delete('/devis/{quote}', [AdminQuoteController::class, 'destroy'])->name('quotes.destroy');

        // Rendez-vous réservés sur le site
        Route::get('/rendez-vous', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::patch('/rendez-vous/{booking}/status', [AdminBookingController::class, 'updateStatus'])->name('bookings.status');
        Route::delete('/rendez-vous/{booking}', [AdminBookingController::class, 'destroy'])->name('bookings.destroy');

        // Messages de contact
        Route::get('/messages', [AdminContactController::class, 'index'])->name('contact.index');
        Route::get('/messages/{contactMessage}', [AdminContactController::class, 'show'])->name('contact.show');
        Route::patch('/messages/{contactMessage}/status', [AdminContactController::class, 'updateStatus'])->name('contact.status');
        Route::delete('/messages/{contactMessage}', [AdminContactController::class, 'destroy'])->name('contact.destroy');
    });

    // Clients du portail et éléments de leur espace
    Route::middleware('can:clients.manage')->group(function () {
        Route::resource('clients', AdminClientController::class);
        Route::post('/clients/{client}/apercu', [AdminClientController::class, 'preview'])->name('clients.preview');
        Route::post('/clients/{client}/elements', [AdminPortalItemController::class, 'store'])->name('clients.items.store');
        Route::put('/elements/{item}', [AdminPortalItemController::class, 'update'])->name('portal-items.update');
        Route::delete('/elements/{item}', [AdminPortalItemController::class, 'destroy'])->name('portal-items.destroy');
    });

    // Médiathèque (images des sections)
    Route::middleware('can:media.manage')->group(function () {
        Route::get('/medias', [AdminMediaController::class, 'index'])->name('media.index');
        Route::post('/medias', [AdminMediaController::class, 'store'])->middleware('throttle:30,1')->name('media.store');
        Route::put('/medias/{media}', [AdminMediaController::class, 'update'])->name('media.update');
        Route::delete('/medias/{media}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
    });

    // Pages & sections (CMS du front Next.js)
    Route::middleware('can:pages.manage')->group(function () {
        Route::resource('pages', AdminPageController::class)->except('show');
        Route::resource('pages.sections', AdminPageSectionController::class)->shallow()->only(['store', 'edit', 'update', 'destroy']);
        Route::patch('/sections/{section}/visibility', [AdminPageSectionController::class, 'visibility'])->name('sections.visibility');
        Route::patch('/sections/{section}/move', [AdminPageSectionController::class, 'move'])->name('sections.move');
        Route::patch('/pages/{page}/sections/ordre', [AdminPageSectionController::class, 'reorder'])->name('pages.sections.reorder');
        Route::post('/sections/{section}/dupliquer', [AdminPageSectionController::class, 'duplicate'])->name('sections.duplicate');
        Route::post('/sections/{section}/apercu', [AdminPageSectionController::class, 'live'])->middleware('throttle:120,1')->name('sections.live');

        // Workflow éditorial : publication, annulation, révisions, aperçu
        Route::post('/pages/{page}/publier', [AdminPageController::class, 'publish'])->middleware('can:pages.publish')->name('pages.publish');
        Route::post('/pages/{page}/annuler-modifications', [AdminPageController::class, 'discard'])->name('pages.discard');
        Route::post('/pages/{page}/revisions/{revision}/restaurer', [AdminPageController::class, 'restore'])->scopeBindings()->name('pages.restore');
        Route::get('/pages/{page}/apercu', [AdminPageController::class, 'preview'])->name('pages.preview');
    });

    // Contenus structurés : services, réalisations, témoignages, FAQ
    Route::middleware('can:content.manage')->group(function () {
        Route::resource('services', AdminServiceController::class)->except('show');
        Route::resource('projets', AdminProjectController::class)->names('projects')->parameters(['projets' => 'project'])->except('show');
        Route::resource('temoignages', AdminTestimonialController::class)->names('testimonials')->parameters(['temoignages' => 'testimonial']);
        Route::resource('faqs', AdminFaqController::class)->except('show');
    });

    // Blog : chaque article est ensuite contrôlé par la Gate « posts.edit » (PostController)
    Route::resource('articles', AdminPostController::class)->names('posts')->parameters(['articles' => 'post'])->except('show')->middleware('can:posts.write');
    Route::get('/articles/{post}/apercu', [AdminPostController::class, 'preview'])->middleware('can:posts.write')->name('posts.preview');

    // Réglages globaux du site et menus du front
    Route::middleware('can:settings.manage')->group(function () {
        Route::get('/parametres', [AdminSettingController::class, 'index'])->name('settings.index');
        Route::post('/parametres', [AdminSettingController::class, 'update'])->name('settings.update');
    });
    Route::middleware('can:menus.manage')->group(function () {
        Route::get('/menus', [AdminMenuController::class, 'index'])->name('menus.index');
        Route::patch('/menus/ordre', [AdminMenuController::class, 'reorder'])->name('menus.reorder');
        Route::post('/menus/{menu}/elements', [AdminMenuController::class, 'store'])->name('menus.items.store');
        Route::get('/menus/elements/{item}/modifier', [AdminMenuController::class, 'edit'])->name('menus.items.edit');
        Route::put('/menus/elements/{item}', [AdminMenuController::class, 'update'])->name('menus.items.update');
        Route::patch('/menus/elements/{item}/move', [AdminMenuController::class, 'move'])->name('menus.items.move');
        Route::delete('/menus/elements/{item}', [AdminMenuController::class, 'destroy'])->name('menus.items.destroy');
    });

    // Équipe : comptes et rôles, journal d'activité
    Route::get('/journal', [AdminActivityController::class, 'index'])->middleware('can:users.manage')->name('activity.index');
    Route::resource('utilisateurs', AdminUserController::class)->names('users')->parameters(['utilisateurs' => 'member'])->except(['show', 'destroy'])->middleware('can:users.manage');
});
