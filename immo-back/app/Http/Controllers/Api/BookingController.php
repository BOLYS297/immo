<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\LeadNotifier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /**
     * GET /api/v1/bookings/taken — créneaux déjà réservés dans les 31 prochains jours.
     * Le front génère les créneaux proposés et retire ceux-ci.
     */
    public function taken(): JsonResponse
    {
        $slots = Booking::upcoming()
            ->where('slot', '<=', now()->addDays(31))
            ->orderBy('slot')
            ->get()
            ->map(fn (Booking $booking) => $booking->slotIso());

        return response()->json(['slots' => $slots])->header('Cache-Control', 'no-store');
    }

    /** POST /api/v1/bookings — réservation d'un rendez-vous depuis la page /contact du front. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120'],
            'company' => ['required', 'string', 'max:100'],
            'need' => ['required', Rule::in(array_keys(Booking::NEEDS))],
            'deadline' => ['required', Rule::in(array_keys(Booking::DEADLINES))],
            'slot' => ['required', 'date', 'after:now', 'before:+31 days'],
            'locale' => ['nullable', Rule::in(array_keys(config('cms.locales')))],
        ]);

        // Converti en UTC avant l'enregistrement : Eloquent écrit l'heure sans tenir compte du décalage.
        $slot = Carbon::parse($validated['slot'])->utc();

        if (Booking::active()->where('slot', $slot)->exists()) {
            return response()->json(['error' => 'taken'], 409);
        }

        try {
            $booking = Booking::create([
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'company' => trim($validated['company']),
                'need' => $validated['need'],
                'deadline' => $validated['deadline'],
                'slot' => $slot,
                'status' => Booking::STATUS_CONFIRMED,
                'locale' => $validated['locale'] ?? null,
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['error' => 'taken'], 409); // réservé entre-temps par une requête simultanée
        }

        $when = $booking->slot->copy()->setTimezone(Booking::TIMEZONE)->format('d/m/Y à H:i');
        LeadNotifier::send(
            "Nouveau rendez-vous le {$when} — {$booking->company}",
            "{$booking->name} <{$booking->email}>\nCréneau : {$when} (heure de Douala)\nBesoin : ".Booking::NEEDS[$booking->need].' · Échéance : '.Booking::DEADLINES[$booking->deadline]."\n\n".route('admin.bookings.index'),
            'booking_webhook',
            ['slot' => $booking->slotIso()] + collect($validated)->only(['name', 'email', 'company', 'need', 'deadline'])->all(),
        );

        return response()->json(['ok' => true], 201);
    }
}
