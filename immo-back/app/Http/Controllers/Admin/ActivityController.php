<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Journal d'activité de l'administration (administrateurs). */
class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'user' => ['nullable', 'integer'],
            'action' => ['nullable', Rule::in(array_keys(config('admin.activity.actions')))],
            'type' => ['nullable', Rule::in(array_keys(config('admin.activity.types')))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $logs = ActivityLog::query()
            ->when($filters['user'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('subject_type', $type))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<', now()->parse($to)->addDay()))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.activity.index', [
            'logs' => $logs,
            'filters' => $filters,
            'members' => User::staff()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
