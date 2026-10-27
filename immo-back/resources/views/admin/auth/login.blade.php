<!DOCTYPE html>
<html lang="fr" data-theme="light" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        (function() {
            let saved = 'light';
            try { saved = localStorage.getItem('immo_theme') === 'dark' ? 'dark' : 'light'; } catch (e) {}
            document.documentElement.setAttribute('data-theme', saved);
            document.documentElement.setAttribute('data-bs-theme', saved);
        })();
    </script>
    <title>Connexion — Administration {{ \App\Services\SettingService::get('site_name') ?? 'IMMO' }}</title>

    <link rel="icon" type="image/png" href="{{ asset('images/symbol-mark.png') }}">
    <link rel="stylesheet" href="{{ asset('vendor/outfit/outfit.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-5.3.3/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons-1.11.3/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body class="login-page position-relative overflow-hidden">

    <img src="{{ asset('images/symbol-mark.png') }}" alt="" class="login-mark" aria-hidden="true">

    <main class="login-card card-admin p-4 p-sm-5 position-relative">
        <div class="text-center mb-4">
            <span class="admin-brand mb-4" translate="no">
                <img src="{{ asset('images/symbol-mark.png') }}" alt="" class="brand-light">
                <img src="{{ asset('images/symbol-dark.png') }}" alt="" class="brand-dark">
                <span class="rule" aria-hidden="true"></span>
                <span class="word text-start"><strong>IMMO</strong><small>DIGITAL</small></span>
            </span>
            <h1 class="h3 mb-1">Espace administration</h1>
            <p class="text-secondary small mb-0">Connectez-vous pour gérer le site, les demandes et les espaces clients.</p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger border-0 small py-2 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ session('error') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning border-0 small py-2 mb-3" role="alert">
                <i class="bi bi-exclamation-circle-fill me-1"></i>{{ session('warning') }}
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info border-0 small py-2 mb-3" role="alert">
                <i class="bi bi-info-circle-fill me-1"></i>{{ session('info') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger border-0 small py-2 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('admin.login.submit') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold">Adresse e-mail</label>
                <input type="email" name="email" id="email" class="form-control py-2" value="{{ old('email') }}" autocomplete="username" required autofocus>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label small fw-semibold">Mot de passe</label>
                <input type="password" name="password" id="password" class="form-control py-2" autocomplete="current-password" required>
            </div>

            <div class="form-check mb-4">
                <input type="checkbox" name="remember" id="remember" class="form-check-input" value="1">
                <label for="remember" class="form-check-label text-secondary small">Se souvenir de moi</label>
            </div>

            <button type="submit" class="btn btn-info w-100 py-2 fs-6">
                <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
            </button>
        </form>

        <div class="text-center mt-4 pt-3" style="border-top: 1px solid var(--line);">
            <a href="{{ config('services.front.url') }}" class="text-secondary small text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>Retour au site
            </a>
        </div>
    </main>

    <script src="{{ asset('js/password-toggle.js') }}?v={{ filemtime(public_path('js/password-toggle.js')) }}"></script>

</body>
</html>
