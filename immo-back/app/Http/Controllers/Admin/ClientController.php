<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Comptes clients du portail (users.role = client). Le paramètre {client} ne résout que des clients. */
class ClientController extends Controller
{
    public function index(): View
    {
        $clients = User::clients()->withCount('portalItems')->orderBy('company')->orderBy('name')->paginate(20);

        return view('admin.clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('admin.clients.create', ['client' => new User(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $client = User::create($this->validated($request) + ['role' => User::ROLE_CLIENT]);

        return redirect()->route('admin.clients.show', $client)->with('success', 'Client créé. Communiquez-lui son mot de passe temporaire par un canal sûr.');
    }

    public function show(User $client): View
    {
        $items = $client->portalItems()->latest()->get()->groupBy('type');
        $types = config('portal.types');

        return view('admin.clients.show', compact('client', 'items', 'types'));
    }

    public function edit(User $client): View
    {
        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, User $client): RedirectResponse
    {
        $data = $this->validated($request, $client);
        $client->update($data);

        // Nouveau mot de passe ou compte désactivé : les connexions ouvertes sur le portail sont fermées.
        if (isset($data['password']) || ! $client->is_active) {
            $client->tokens()->delete();
        }

        return redirect()->route('admin.clients.show', $client)->with('success', 'Client mis à jour.');
    }

    public function destroy(User $client): RedirectResponse
    {
        $client->tokens()->delete();
        $client->delete(); // ses éléments du portail sont supprimés en cascade

        return redirect()->route('admin.clients.index')->with('success', 'Client et éléments de son portail supprimés.');
    }

    /** Ouvre le portail du client sur le front, en lecture seule, via un code à usage unique valable 2 minutes. */
    public function preview(User $client): RedirectResponse
    {
        $code = Str::random(40);
        Cache::put("portal-preview:{$code}", $client->id, now()->addMinutes(2));

        return redirect()->away(config('services.front.url').'/api/portal/preview?code='.$code);
    }

    private function validated(Request $request, ?User $client = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'company' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($client)],
            'password' => [$client ? 'nullable' : 'required', 'string', 'min:10', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $data = [
            'name' => trim($validated['name']),
            'company' => filled($validated['company'] ?? null) ? trim($validated['company']) : null,
            'email' => strtolower(trim($validated['email'])),
            'is_active' => $request->boolean('is_active'),
        ];

        return filled($validated['password'] ?? null) ? $data + ['password' => $validated['password']] : $data;
    }
}
