<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'groups' => config('cms.settings'),
            'locales' => config('cms.locales'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(SettingService::rules(), [
            'regex' => 'Le champ « :attribute » n\'a pas un format valide (lien : /contact ou https://… ; téléphone : +237 6xx xx xx xx).',
        ], SettingService::attributes());

        SettingService::save($validated['settings'] ?? []);

        return back()->with('success', 'Réglages du site enregistrés. Le site public est mis à jour.');
    }
}
