<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientProject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Projets d'un client du portail : chaque élément de son espace (tâche, jalon, livrable, document, ticket) appartient à un projet. */
class ClientProjectController extends Controller
{
    public function store(Request $request, User $client): RedirectResponse
    {
        $project = $client->clientProjects()->create($this->validated($request));

        return redirect()->route('admin.clients.show', ['client' => $client, 'projet' => $project->id])->with('success', 'Projet créé. Ajoutez-y ses tâches, jalons, livrables et documents.');
    }

    public function update(Request $request, ClientProject $clientProject): RedirectResponse
    {
        $clientProject->update($this->validated($request));

        return redirect()->route('admin.clients.show', ['client' => $clientProject->user_id, 'projet' => $clientProject->id])->with('success', 'Projet mis à jour.');
    }

    public function destroy(ClientProject $clientProject): RedirectResponse
    {
        // Un par un : chaque élément supprime son fichier joint.
        $clientProject->items->each->delete();
        $clientProject->delete();

        return redirect()->route('admin.clients.show', $clientProject->user_id)->with('success', "Projet « {$clientProject->name} » et ses éléments supprimés.");
    }

    /** @return array{name: string, status: string, summary: string|null} */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'status' => ['required', Rule::in(array_keys(config('portal.project_statuses')))],
            'summary' => ['nullable', 'string', 'max:300'],
        ], [], ['name' => 'Nom du projet', 'status' => 'Statut', 'summary' => 'Description']);

        return [
            'name' => trim($validated['name']),
            'status' => $validated['status'],
            'summary' => filled($validated['summary'] ?? null) ? trim($validated['summary']) : null,
        ];
    }
}
