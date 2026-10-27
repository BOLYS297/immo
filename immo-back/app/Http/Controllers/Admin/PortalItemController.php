<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortalItem;
use App\Models\User;
use App\Services\PortalItemSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PortalItemController extends Controller
{
    public function store(Request $request, User $client): RedirectResponse
    {
        $type = $request->validate(['type' => ['required', Rule::in(array_keys(config('portal.types')))]])['type'];
        $validated = $request->validate(PortalItemSchema::rules($type), [], PortalItemSchema::attributes($type));

        $client->portalItems()->create([
            'type' => $type,
            'title' => trim($validated['title']),
            'data' => PortalItemSchema::clean($type, $request->input('data', [])),
        ]);

        return redirect()->to(route('admin.clients.show', $client)."#type-{$type}")->with('success', 'Élément ajouté au portail du client.');
    }

    public function update(Request $request, PortalItem $item): RedirectResponse
    {
        $validated = $request->validate(PortalItemSchema::rules($item->type), [], PortalItemSchema::attributes($item->type));
        $data = PortalItemSchema::clean($item->type, $request->input('data', []));

        // La trace de signature du client est conservée tant que le document reste signé ou payé.
        if ($item->type === 'document' && in_array($data['status'], ['signed', 'paid'], true)) {
            $data += array_intersect_key($item->data, array_flip(['signedAt', 'signedBy']));
        }

        $item->update(['title' => trim($validated['title']), 'data' => $data]);

        return redirect()->to(route('admin.clients.show', $item->user_id)."#type-{$item->type}")->with('success', 'Élément mis à jour.');
    }

    public function destroy(PortalItem $item): RedirectResponse
    {
        $item->delete();

        return redirect()->to(route('admin.clients.show', $item->user_id)."#type-{$item->type}")->with('success', 'Élément supprimé.');
    }
}
