<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\LeadNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /** POST /api/v1/messages — formulaire « Écrivez-nous » du front (bloc contact_form) → admin « Messages ». */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9 ().-]{6,30}$/'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
        ]);

        $message = ContactMessage::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
            'subject' => trim($validated['subject']),
            'message' => trim($validated['message']),
            'status' => ContactMessage::STATUS_NEW,
        ]);

        LeadNotifier::send(
            "Nouveau message — {$message->subject}",
            "{$message->name} <{$message->email}>".($message->phone ? " · {$message->phone}" : '')."\n\n{$message->message}\n\n".route('admin.contact.show', $message),
            'message_webhook',
            ['id' => $message->id] + collect($validated)->only(['name', 'email', 'phone', 'subject', 'message'])->all(),
        );

        return response()->json(['ok' => true], 201);
    }
}
