@extends('layouts.admin')

@php($labels = config('admin.activity'))

@section('title', 'Journal d\'activité')
@section('page_title', 'Journal d\'activité')

@section('content')

    <p class="text-secondary small mb-4">
        Actions de l'équipe dans l'administration. Seuls les noms des champs modifiés sont enregistrés, jamais leur contenu.
        Conservation : {{ \App\Models\ActivityLog::RETENTION_MONTHS }} mois.
    </p>

    <form method="GET" class="card-admin p-3 mb-4" aria-label="Filtrer le journal">
        <div class="row g-2 align-items-end">
            <div class="col-sm-6 col-xl">
                <label for="f_user" class="form-label small">Personne</label>
                <select name="user" id="f_user" class="form-select form-select-sm">
                    <option value="">Toute l'équipe</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" @selected((string) ($filters['user'] ?? '') === (string) $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-xl">
                <label for="f_action" class="form-label small">Action</label>
                <select name="action" id="f_action" class="form-select form-select-sm">
                    <option value="">Toutes</option>
                    @foreach($labels['actions'] as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['action'] ?? '') === $key)>{{ ucfirst($label) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-xl">
                <label for="f_type" class="form-label small">Contenu</label>
                <select name="type" id="f_type" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    @foreach($labels['types'] as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ ucfirst(preg_replace("/^(la |le |l')/u", '', $label)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-xl-2">
                <label for="f_from" class="form-label small">Du</label>
                <input type="date" name="from" id="f_from" class="form-control form-control-sm" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div class="col-6 col-xl-2">
                <label for="f_to" class="form-label small">Au</label>
                <input type="date" name="to" id="f_to" class="form-control form-control-sm" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-info px-3" title="Filtrer"><i class="bi bi-funnel"></i><span class="visually-hidden">Filtrer</span></button>
                @if(array_filter($filters))
                    <a href="{{ route('admin.activity.index') }}" class="btn btn-sm btn-outline-secondary" title="Effacer les filtres"><i class="bi bi-x-lg"></i><span class="visually-hidden">Effacer les filtres</span></a>
                @endif
            </div>
        </div>
    </form>

    <div class="card-admin p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Action</th>
                        <th scope="col">Détails</th>
                        <th scope="col">Adresse IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php($route = $labels['routes'][$log->subject_type] ?? null)
                        @php($failed = $log->action === 'auth.failed')
                        <tr>
                            <td class="small text-nowrap" title="{{ $log->created_at->format('d/m/Y H:i:s') }}">
                                {{ $log->created_at->format('d/m/Y H:i') }}
                                <div class="text-secondary">{{ $log->created_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                @if($failed)
                                    <span class="badge bg-warning text-dark me-1">{{ $labels['actions']['auth.failed'] }}</span>
                                    <span class="small">pour {{ $log->details['email'] ?? '?' }}</span>
                                @else
                                    <strong class="text-white">{{ $log->user_name ?? 'Compte supprimé' }}</strong>
                                    {{ $labels['actions'][$log->action] ?? $log->action }}
                                    @if($log->subject_type && ! str_starts_with($log->action, 'auth.'))
                                        {{ $labels['types'][$log->subject_type] ?? $log->subject_type }}
                                        @if($route && $log->subject_id && $log->action !== 'deleted')
                                            <a href="{{ route($route, $log->subject_id) }}" class="text-info">« {{ Str::limit($log->subject_label, 70) }} »</a>
                                        @else
                                            « {{ Str::limit($log->subject_label, 70) }} »
                                        @endif
                                    @endif
                                @endif
                            </td>
                            <td class="small text-secondary">
                                @if(! empty($log->details['fields']))
                                    <span class="font-monospace">{{ implode(', ', $log->details['fields']) }}</span>
                                @elseif(isset($log->details['from']))
                                    {{ config('admin.roles.'.$log->details['from'].'.label', $log->details['from'] ?? '—') }} → {{ config('admin.roles.'.$log->details['to'].'.label', $log->details['to']) }}
                                @elseif(isset($log->details['revision']))
                                    version du {{ \Illuminate\Support\Carbon::parse($log->details['revision'])->format('d/m/Y H:i') }}
                                @endif
                            </td>
                            <td class="small font-monospace text-secondary">{{ $log->ip }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-secondary py-5">Aucune action enregistrée{{ array_filter($filters) ? ' pour ces filtres' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $logs->links('pagination::bootstrap-5') }}</div>

@endsection
