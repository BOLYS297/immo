<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientProject;
use App\Models\PortalItem;
use App\Services\PortalItemSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalItemController extends Controller
{
    public function store(Request $request, ClientProject $clientProject): RedirectResponse
    {
        $type = $request->validate(['type' => ['required', Rule::in(array_keys(config('portal.types')))]])['type'];
        $validated = $request->validate(PortalItemSchema::rules($type), [], PortalItemSchema::attributes($type));

        $clientProject->items()->create([
            'user_id' => $clientProject->user_id,
            'type' => $type,
            'title' => trim($validated['title']),
            'data' => PortalItemSchema::clean($type, $request->input('data', [])) + PortalItemSchema::files($type, $request, $clientProject->user_id),
        ]);

        return $this->backTo($clientProject, $type, 'Élément ajouté au projet.');
    }

    public function update(Request $request, PortalItem $item): RedirectResponse
    {
        $validated = $request->validate(
            PortalItemSchema::rules($item->type) + ['project' => ['nullable', 'integer', Rule::exists('client_projects', 'id')->where('user_id', $item->user_id)]],
            [],
            PortalItemSchema::attributes($item->type) + ['project' => 'Projet'],
        );
        $data = PortalItemSchema::clean($item->type, $request->input('data', []));

        // La trace de signature du client est conservée tant que le document reste signé ou payé.
        if ($item->type === 'document' && in_array($data['status'], ['signed', 'paid'], true)) {
            $data += array_intersect_key($item->data, array_flip(['signedAt', 'signedBy']));
        }
        $data += PortalItemSchema::files($item->type, $request, $item->user_id, $item);

        $item->update([
            'title' => trim($validated['title']),
            'data' => $data,
            'client_project_id' => (int) ($validated['project'] ?? $item->client_project_id),
        ]);

        return $this->backTo($item->fresh()->project, $item->type, 'Élément mis à jour.');
    }

    public function destroy(PortalItem $item): RedirectResponse
    {
        $item->delete();

        return $this->backTo($item->project, $item->type, 'Élément supprimé.');
    }

    /** Fichier joint d'un document, ouvert dans le navigateur (PDF, image). */
    public function file(PortalItem $item): StreamedResponse|RedirectResponse
    {
        $file = $item->file();
        if (! $file || ! Storage::disk(PortalItem::FILE_DISK)->exists($file['path'])) {
            return back()->with('error', 'Le fichier joint est introuvable.');
        }

        return Storage::disk(PortalItem::FILE_DISK)->response($file['path'], $file['name'], ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    private function backTo(?ClientProject $project, string $type, string $message): RedirectResponse
    {
        return redirect()->to(route('admin.clients.show', ['client' => $project?->user_id, 'projet' => $project?->id])."#type-{$type}")->with('success', $message);
    }
}
