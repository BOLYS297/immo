<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status');
        $query = ContactMessage::latest();

        if ($statusFilter && in_array($statusFilter, [
            ContactMessage::STATUS_NEW,
            ContactMessage::STATUS_READ,
            ContactMessage::STATUS_PROCESSED,
            ContactMessage::STATUS_ARCHIVED,
        ])) {
            $query->where('status', $statusFilter);
        }

        $messages = $query->paginate(15)->withQueryString();

        $statusCounts = [
            'all' => ContactMessage::count(),
            ContactMessage::STATUS_NEW => ContactMessage::where('status', ContactMessage::STATUS_NEW)->count(),
            ContactMessage::STATUS_READ => ContactMessage::where('status', ContactMessage::STATUS_READ)->count(),
            ContactMessage::STATUS_PROCESSED => ContactMessage::where('status', ContactMessage::STATUS_PROCESSED)->count(),
            ContactMessage::STATUS_ARCHIVED => ContactMessage::where('status', ContactMessage::STATUS_ARCHIVED)->count(),
        ];

        return view('admin.contact.index', compact('messages', 'statusFilter', 'statusCounts'));
    }

    public function show(ContactMessage $contactMessage): View
    {
        if ($contactMessage->status === ContactMessage::STATUS_NEW) {
            $contactMessage->update(['status' => ContactMessage::STATUS_READ]);
        }

        return view('admin.contact.show', ['message' => $contactMessage]);
    }

    public function updateStatus(Request $request, ContactMessage $contactMessage): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:nouveau,lu,traite,archive'],
        ]);

        $contactMessage->update(['status' => $validated['status']]);

        return back()->with('success', 'Statut du message mis à jour.');
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->delete();

        return redirect()->route('admin.contact.index')->with('success', 'Message supprimé.');
    }
}
