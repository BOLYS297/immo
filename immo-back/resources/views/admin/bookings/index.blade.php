@extends('layouts.admin')

@section('title', 'Rendez-vous')
@section('page_title', 'Rendez-vous de cadrage')

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h5 class="text-white fw-bold mb-1">Sessions de 30 minutes réservées sur le site</h5>
            <p class="text-secondary small mb-0">Horaires en heure de Douala. Annuler un rendez-vous libère son créneau sur le site.</p>
        </div>
        <div class="btn-group" role="group" aria-label="Période">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm {{ $period === 'a-venir' ? 'btn-info' : 'btn-outline-secondary' }}">À venir</a>
            <a href="{{ route('admin.bookings.index', ['periode' => 'passes']) }}" class="btn btn-sm {{ $period === 'passes' ? 'btn-info' : 'btn-outline-secondary' }}">Passés</a>
        </div>
    </div>

    <div class="card-admin p-4">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle small mb-0">
                <thead>
                    <tr class="text-secondary border-secondary border-opacity-25">
                        <th>Créneau</th>
                        <th>Prospect</th>
                        <th>Besoin & Échéance</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        @php($slot = $booking->slot->copy()->setTimezone(\App\Models\Booking::TIMEZONE))
                        <tr class="{{ $booking->status === \App\Models\Booking::STATUS_CANCELLED ? 'opacity-50' : '' }}">
                            <td>
                                <div class="text-white fw-bold fs-6">{{ $slot->locale('fr')->isoFormat('ddd D MMM') }} · {{ $slot->format('H:i') }}</div>
                                <div class="text-secondary" style="font-size: 0.75rem;">Reçu le {{ $booking->created_at->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                <div class="text-white fw-semibold">{{ $booking->name }}</div>
                                <div class="text-info" style="font-size: 0.75rem;"><i class="bi bi-building me-1"></i>{{ $booking->company }}</div>
                                <a href="mailto:{{ $booking->email }}" class="text-secondary text-decoration-none" style="font-size: 0.75rem;"><i class="bi bi-envelope me-1"></i>{{ $booking->email }}</a>
                            </td>
                            <td>
                                <div class="text-white">{{ \App\Models\Booking::NEEDS[$booking->need] ?? $booking->need }}</div>
                                <div class="text-secondary" style="font-size: 0.75rem;">{{ \App\Models\Booking::DEADLINES[$booking->deadline] ?? $booking->deadline }}</div>
                            </td>
                            <td><span class="badge {{ $booking->status_badge_class }}">{{ $booking->status_label }}</span></td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <form action="{{ route('admin.bookings.status', $booking) }}" method="POST" class="d-flex gap-1">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary border-opacity-25" aria-label="Statut du rendez-vous" onchange="this.form.submit()">
                                            @foreach([\App\Models\Booking::STATUS_CONFIRMED => 'Confirmé', \App\Models\Booking::STATUS_DONE => 'Honoré', \App\Models\Booking::STATUS_CANCELLED => 'Annulé'] as $value => $label)
                                                <option value="{{ $value }}" @selected($booking->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                    <form action="{{ route('admin.bookings.destroy', $booking) }}" method="POST" onsubmit="return confirm('Supprimer définitivement ce rendez-vous ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">
                                {{ $period === 'passes' ? 'Aucun rendez-vous passé.' : 'Aucun rendez-vous à venir.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $bookings->links('pagination::bootstrap-5') }}
        </div>
    </div>

@endsection
