{{-- Accès refusé. Dans l'admin : rubrique non autorisée par le rôle (config/admin.php), page aux couleurs de l'admin. --}}
@if(auth()->user()?->isStaff())
    @include('admin.forbidden')
@else
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Accès refusé</title></head>
<body style="font-family: system-ui, sans-serif; text-align: center; padding: 4rem 1rem;"><h1>Accès refusé</h1></body></html>
@endif
