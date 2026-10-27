<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password'], 'is_active' => true], $remember)) {
            $user = Auth::user();
            if (! $user->isStaff()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->with('error', 'Vous n\'avez pas les droits d\'accès à l\'administration.');
            }

            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'))->with('success', 'Bienvenue dans votre espace d\'administration, '.$user->name);
        }

        return back()->withInput($request->only('email'))->with('error', 'Identifiants invalides ou compte inactif.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('info', 'Vous avez été déconnecté avec succès.');
    }
}
