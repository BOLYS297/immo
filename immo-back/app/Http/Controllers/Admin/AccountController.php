<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** « Mon compte » : chaque membre de l'équipe modifie son nom, son e-mail et son mot de passe. */
class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.account.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:191', Rule::unique('users', 'email')->ignore($user)],
            // Mot de passe actuel exigé pour changer l'e-mail de connexion ou le mot de passe.
            'current_password' => [Rule::requiredIf(fn () => $request->filled('password') || $request->input('email') !== $user->email), 'nullable', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(12)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'current_password.required' => 'Saisissez votre mot de passe actuel pour changer votre e-mail ou votre mot de passe.',
        ], ['name' => 'nom', 'password' => 'nouveau mot de passe']);

        $user->fill(['name' => $validated['name'], 'email' => $validated['email']]);
        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }
        $user->save();

        if (filled($validated['password'] ?? null)) {
            // Les autres sessions (ancien mot de passe) sont fermées par le middleware auth.session.
            $request->session()->regenerate();
        }

        return back()->with('success', 'Votre compte est à jour.');
    }
}
