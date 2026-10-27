<!DOCTYPE html>
<html lang="fr" data-theme="light" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <script>
        // Thème choisi appliqué avant l'affichage (pas de flash). Clair par défaut, comme le site public.
        (function() {
            let saved = 'light';
            try { saved = localStorage.getItem('immo_theme') === 'dark' ? 'dark' : 'light'; } catch (e) {}
            document.documentElement.setAttribute('data-theme', saved);
            document.documentElement.setAttribute('data-bs-theme', saved);
        })();
    </script>
    <title>@yield('title', 'Administration') — {{ \App\Services\SettingService::get('site_name') ?? 'IMMO' }}</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/symbol-mark.png') }}">

    <!-- Outfit : police du site public (Gilroy prise en priorité si elle est installée sur le poste) -->
    <link rel="stylesheet" href="{{ asset('vendor/outfit/outfit.css') }}">

    <!-- Bootstrap 5.3.3 & Bootstrap Icons, puis le thème IMMO -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-5.3.3/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons-1.11.3/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    @stack('styles')
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar p-3" id="adminSidebar">
        <!-- Marque : symbole + filet + mot-symbole, comme le logo du site -->
        <div class="d-flex align-items-center justify-content-between mb-2 px-2 pt-2 flex-shrink-0">
            <a href="{{ route('admin.dashboard') }}" class="admin-brand" translate="no">
                <img src="{{ asset('images/symbol-mark.png') }}" alt="" class="brand-light">
                <img src="{{ asset('images/symbol-dark.png') }}" alt="" class="brand-dark">
                <span class="rule" aria-hidden="true"></span>
                <span class="word"><strong>IMMO</strong><small>DIGITAL</small></span>
                <span class="tag">ADMIN</span>
            </a>
            <button class="round-btn d-lg-none" id="sidebarCloseBtn" aria-label="Fermer le menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        @php
            // Compteurs calculés seulement pour les rubriques visibles par le rôle connecté.
            $canLeads = auth()->user()->can('leads.manage');
            $unreadQuotesCount = $canLeads ? \App\Models\QuoteRequest::where('status', \App\Models\QuoteRequest::STATUS_NEW)->count() : 0;
            $unreadMessagesCount = $canLeads ? \App\Models\ContactMessage::where('status', \App\Models\ContactMessage::STATUS_NEW)->count() : 0;
            $upcomingBookingsCount = $canLeads ? \App\Models\Booking::upcoming()->where('status', \App\Models\Booking::STATUS_CONFIRMED)->count() : 0;
            $openTicketsCount = auth()->user()->can('clients.manage') ? \App\Models\PortalItem::where('type', 'ticket')->where('data->status', 'open')->count() : 0;
        @endphp

        <!-- Nav Links with Scrollbar -->
        <div class="sidebar-scroll pe-1">
            <div class="sidebar-section mt-2">Navigation</div>

            <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}">
                <i class="bi bi-grid"></i>
                <span>Tableau de bord</span>
            </a>

            @can('leads.manage')
                <div class="sidebar-section">Prospects & Devis</div>

                <a href="{{ route('admin.quotes.index') }}" class="admin-nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('admin.quotes.*') ? 'active' : '' }}">
                    <div>
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Demandes de devis</span>
                    </div>
                    @if($unreadQuotesCount > 0)
                        <span class="badge bg-danger rounded-pill">{{ $unreadQuotesCount }}</span>
                    @endif
                </a>

                <a href="{{ route('admin.bookings.index') }}" class="admin-nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                    <div>
                        <i class="bi bi-calendar-check"></i>
                        <span>Rendez-vous</span>
                    </div>
                    @if($upcomingBookingsCount > 0)
                        <span class="badge bg-info text-dark rounded-pill" title="Rendez-vous à venir">{{ $upcomingBookingsCount }}</span>
                    @endif
                </a>

                <a href="{{ route('admin.contact.index') }}" class="admin-nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('admin.contact.*') ? 'active' : '' }}">
                    <div>
                        <i class="bi bi-chat-left-dots"></i>
                        <span>Messages</span>
                    </div>
                    @if($unreadMessagesCount > 0)
                        <span class="badge bg-primary rounded-pill">{{ $unreadMessagesCount }}</span>
                    @endif
                </a>
            @endcan

            @can('clients.manage')
                <div class="sidebar-section">Portail Client</div>

                <a href="{{ route('admin.clients.index') }}" class="admin-nav-link d-flex justify-content-between align-items-center {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">
                    <div>
                        <i class="bi bi-person-badge"></i>
                        <span>Clients</span>
                    </div>
                    @if($openTicketsCount > 0)
                        <span class="badge bg-warning text-dark rounded-pill" title="Tickets ouverts">{{ $openTicketsCount }}</span>
                    @endif
                </a>
            @endcan

            @canany(['pages.manage', 'media.manage', 'content.manage', 'posts.write'])
                <div class="sidebar-section">Gestion du Site</div>
            @endcanany

            @can('pages.manage')
                <a href="{{ route('admin.pages.index') }}" class="admin-nav-link {{ request()->routeIs('admin.pages.*', 'admin.sections.*') ? 'active' : '' }}">
                    <i class="bi bi-window-stack"></i>
                    <span>Pages & Sections</span>
                </a>
            @endcan

            @can('media.manage')
                <a href="{{ route('admin.media.index') }}" class="admin-nav-link {{ request()->routeIs('admin.media.*') ? 'active' : '' }}">
                    <i class="bi bi-images"></i>
                    <span>Médiathèque</span>
                </a>
            @endcan

            @can('content.manage')
                <a href="{{ route('admin.services.index') }}" class="admin-nav-link {{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                    <i class="bi bi-layers"></i>
                    <span>Services</span>
                </a>

                <a href="{{ route('admin.projects.index') }}" class="admin-nav-link {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}">
                    <i class="bi bi-briefcase"></i>
                    <span>Réalisations (Portfolio)</span>
                </a>
            @endcan

            @can('posts.write')
                <a href="{{ route('admin.posts.index') }}" class="admin-nav-link {{ request()->routeIs('admin.posts.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text"></i>
                    <span>{{ auth()->user()->can('posts.manage') ? 'Blog & Articles' : 'Mes articles' }}</span>
                </a>
            @endcan

            @can('content.manage')
                <a href="{{ route('admin.testimonials.index') }}" class="admin-nav-link {{ request()->routeIs('admin.testimonials.*') ? 'active' : '' }}">
                    <i class="bi bi-star"></i>
                    <span>Avis & Témoignages</span>
                </a>

                <a href="{{ route('admin.faqs.index') }}" class="admin-nav-link {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}">
                    <i class="bi bi-question-circle"></i>
                    <span>FAQ</span>
                </a>
            @endcan

            @canany(['settings.manage', 'menus.manage', 'users.manage'])
                <div class="sidebar-section">Configuration</div>
            @endcanany

            @can('settings.manage')
                <a href="{{ route('admin.settings.index') }}" class="admin-nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i class="bi bi-sliders"></i>
                    <span>Réglages du site</span>
                </a>
            @endcan

            @can('menus.manage')
                <a href="{{ route('admin.menus.index') }}" class="admin-nav-link {{ request()->routeIs('admin.menus.*') ? 'active' : '' }}">
                    <i class="bi bi-list-nested"></i>
                    <span>Menus</span>
                </a>
            @endcan

            @can('users.manage')
                <a href="{{ route('admin.users.index') }}" class="admin-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i>
                    <span>Utilisateurs & rôles</span>
                </a>

                <a href="{{ route('admin.activity.index') }}" class="admin-nav-link {{ request()->routeIs('admin.activity.*') ? 'active' : '' }}">
                    <i class="bi bi-clock-history"></i>
                    <span>Journal d'activité</span>
                </a>
            @endcan
        </div>

        <!-- Sidebar footer -->
        <div class="pt-3 mt-2 flex-shrink-0" style="border-top: 1px solid var(--line);">
            <a href="{{ config('services.front.url') }}" target="_blank" class="admin-nav-link">
                <i class="bi bi-box-arrow-up-right"></i>
                <span>Voir le site public</span>
            </a>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="admin-nav-link text-danger w-100 bg-transparent border-0 text-start">
                    <i class="bi bi-box-arrow-left"></i>
                    <span>Déconnexion</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <!-- Topbar -->
        <header class="topbar px-3 px-md-4 d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <button class="round-btn d-lg-none" id="sidebarToggleBtn" aria-label="Ouvrir le menu">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <h1 class="topbar-title mb-0 text-truncate d-none d-sm-block">@yield('page_title', 'Tableau de bord')</h1>
            </div>

            <div class="d-flex align-items-center gap-2 gap-md-3">
                <!-- Langue de l'interface (traduction automatique) -->
                <div class="lang-switch-group" role="group" aria-label="Langue">
                    <button type="button" class="lang-btn active" data-lang="fr" onclick="setAppLanguage('fr')" title="Passer en Français">FR</button>
                    <button type="button" class="lang-btn" data-lang="en" onclick="setAppLanguage('en')" title="Translate to English">EN</button>
                </div>

                <button type="button" class="round-btn" id="themeToggleBtnAdmin" onclick="toggleAppTheme()" title="Mode clair / sombre" aria-label="Basculer le mode clair ou sombre">
                    <i class="bi bi-moon" id="themeIconAdmin"></i>
                </button>

                <div class="text-end d-none d-md-block">
                    <div class="small fw-semibold">{{ auth()->user()->name ?? 'Administrateur' }}</div>
                    <div class="text-secondary" style="font-size: 0.75rem;">{{ auth()->user()->email ?? '' }}</div>
                </div>
                <div class="dropdown">
                    <button class="btn p-0 border-0 rounded-circle" type="button" data-bs-toggle="dropdown" aria-label="Menu du compte">
                        <span class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small text-secondary">Connecté en tant que {{ auth()->user()->roleLabel() }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('admin.account.edit') }}"><i class="bi bi-person-gear me-2"></i>Mon compte</a></li>
                        @can('settings.manage')
                            <li><a class="dropdown-item" href="{{ route('admin.settings.index') }}"><i class="bi bi-gear me-2"></i>Réglages du site</a></li>
                        @endcan
                        <li><a class="dropdown-item" href="{{ config('services.front.url') }}" target="_blank"><i class="bi bi-globe me-2"></i>Site public</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('admin.logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-left me-2"></i>Déconnexion</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="p-3 p-md-4 flex-grow-1">
            <!-- Flash messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 border-0 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 border-0 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show d-flex align-items-center gap-2 border-0 shadow-sm" role="alert">
                    <i class="bi bi-info-circle-fill fs-5"></i>
                    <div>{{ session('info') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="admin-footer px-4 py-3 small d-flex justify-content-between align-items-center">
            <div>&copy; {{ date('Y') }} {{ \App\Services\SettingService::get('site_name') ?? 'IMMO' }} — Espace d'administration</div>
            <div>Version 1.0.0</div>
        </footer>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="{{ asset('vendor/bootstrap-5.3.3/bootstrap.bundle.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('adminSidebar');
            document.getElementById('sidebarToggleBtn')?.addEventListener('click', () => sidebar.classList.toggle('show'));
            document.getElementById('sidebarCloseBtn')?.addEventListener('click', () => sidebar.classList.remove('show'));
        });
    </script>

    <!-- Google Translate Headless Integration -->
    <div id="google_translate_element" style="display:none !important; visibility:hidden !important; position:absolute; left:-9999px;"></div>
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'fr',
                includedLanguages: 'fr,en',
                autoDisplay: false
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

    <!-- Theme & Language Switcher Logic -->
    <script>
        // ─── Mode clair / sombre (clair par défaut) ───
        function toggleAppTheme() {
            const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            document.documentElement.setAttribute('data-bs-theme', next);
            try { localStorage.setItem('immo_theme', next); } catch (e) {}
            updateThemeIcons(next);
        }

        function updateThemeIcons(theme) {
            document.querySelectorAll('#themeIcon, #themeIconAdmin').forEach(icon => {
                icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon';
            });
        }

        // ─── Traduction Automatique FR / EN ───
        function setAppLanguage(lang) {
            localStorage.setItem('immo_lang', lang);
            updateLangButtons(lang);

            if (lang === 'fr') {
                document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; domain=" + window.location.hostname + "; path=/;";
                document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; domain=." + window.location.hostname + "; path=/;";
            } else {
                document.cookie = "googtrans=/fr/" + lang + "; path=/;";
                document.cookie = "googtrans=/fr/" + lang + "; domain=" + window.location.hostname + "; path=/;";
                document.cookie = "googtrans=/fr/" + lang + "; domain=." + window.location.hostname + "; path=/;";
            }

            const combo = document.querySelector('.goog-te-combo');
            if (combo) {
                combo.value = lang;
                combo.dispatchEvent(new Event('change'));
            } else {
                window.location.reload();
            }
        }

        function updateLangButtons(lang) {
            document.querySelectorAll('.lang-btn').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-lang') === lang);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateThemeIcons(document.documentElement.getAttribute('data-theme'));
            const hasEnCookie = document.cookie.indexOf('googtrans=/fr/en') !== -1;
            updateLangButtons(localStorage.getItem('immo_lang') || (hasEnCookie ? 'en' : 'fr'));
        });
    </script>
    <script src="{{ asset('js/admin-editor.js') }}?v={{ filemtime(public_path('js/admin-editor.js')) }}"></script>
    <script src="{{ asset('js/password-toggle.js') }}?v={{ filemtime(public_path('js/password-toggle.js')) }}"></script>
    @stack('scripts')
</body>
</html>
