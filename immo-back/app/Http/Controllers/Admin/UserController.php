<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Activity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Comptes de l'équipe (administration). Pas de suppression : un compte est désactivé,
 * pour garder l'auteur de ses articles et l'historique de ses actions.
 */
class UserController extends Controller
{
    public function index(): View
    {
        $members = User::staff()->withCount('posts')->orderByDesc('is_active')->orderBy('name')->get();

        return view('admin.users.index', ['members' => $members, 'roles' => config('admin.roles')]);
    }

    public function create(): View
    {
        return view('admin.users.create', ['member' => new User(['role' => 'author', 'is_active' => true]), 'roles' => config('admin.roles')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $member = User::create($data);

        return redirect()->route('admin.users.index')->with('success', "Compte créé pour {$member->name} ({$member->roleLabel()}). Transmettez-lui son mot de passe par un canal sûr.");
    }

    public function edit(User $member): View
    {
        return view('admin.users.edit', ['member' => $member, 'roles' => config('admin.roles')]);
    }

    public function update(Request $request, User $member): RedirectResponse
    {
        $data = $this->validated($request, $member);
        $previousRole = $member->staffRole();
        $member->update($data);
        if ($previousRole !== $member->staffRole()) {
            Activity::record('role_changed', $member, ['from' => $previousRole, 'to' => $member->staffRole()]);
        }

        return redirect()->route('admin.users.index')->with('success', "Compte de {$member->name} mis à jour.");
    }

    private function validated(Request $request, ?User $member = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:191', Rule::unique('users', 'email')->ignore($member)],
            'role' => ['required', Rule::in(array_keys(config('admin.roles')))],
            'password' => [$member ? 'nullable' : 'required', 'confirmed', Password::min(12)->letters()->numbers()],
            'is_active' => ['boolean'],
        ], [], ['name' => 'nom', 'role' => 'rôle', 'password' => 'mot de passe']);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active'),
        ];
        if (filled($validated['password'] ?? null)) {
            $data['password'] = $validated['password'];
        }

        if ($member) {
            // Son propre compte : ni changement de rôle ni désactivation, pour ne jamais s'enfermer dehors.
            if ($member->is($request->user()) && ($data['role'] !== $member->staffRole() || ! $data['is_active'])) {
                throw ValidationException::withMessages(['role' => 'Vous ne pouvez pas modifier votre propre rôle ni désactiver votre compte. Demandez-le à un autre administrateur.']);
            }
            // Toujours au moins un administrateur actif.
            $losesAdmin = $member->isAdmin() && ($data['role'] !== User::ROLE_ADMIN || ! $data['is_active']);
            if ($losesAdmin && ! User::staff()->whereKeyNot($member->id)->get()->contains(fn (User $u) => $u->isAdmin())) {
                throw ValidationException::withMessages(['role' => 'C\'est le dernier administrateur actif : nommez d\'abord un autre administrateur.']);
            }
        }

        return $data;
    }
}
