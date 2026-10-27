<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->query('periode') === 'passes' ? 'passes' : 'a-venir';

        $bookings = Booking::query()
            ->when($period === 'a-venir', fn ($q) => $q->where('slot', '>=', now()->startOfDay())->orderBy('slot'))
            ->when($period === 'passes', fn ($q) => $q->where('slot', '<', now()->startOfDay())->orderByDesc('slot'))
            ->paginate(20)
            ->withQueryString();

        return view('admin.bookings.index', compact('bookings', 'period'));
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([Booking::STATUS_CONFIRMED, Booking::STATUS_DONE, Booking::STATUS_CANCELLED])],
        ]);

        try {
            $booking->update($validated);
        } catch (UniqueConstraintViolationException) {
            // Réactivation d'un rendez-vous annulé dont le créneau a été repris entre-temps.
            return back()->with('error', 'Ce créneau a été réservé par quelqu\'un d\'autre : le rendez-vous ne peut pas être réactivé.');
        }

        return back()->with('success', 'Rendez-vous : statut « '.$booking->status_label.' ».');
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $booking->delete();

        return back()->with('success', 'Rendez-vous supprimé.');
    }
}
