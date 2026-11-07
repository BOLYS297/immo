<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Database\Factories\ClientProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Projet réalisé pour un client du portail (distinct des « Réalisations » publiques, App\Models\Project).
 * Tâches, jalons, livrables, documents et tickets du portail sont rangés par projet.
 */
class ClientProject extends Model
{
    /** @use HasFactory<ClientProjectFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = [
        'name',
        'status',
        'summary',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PortalItem::class);
    }

    /** Le plus récent d'abord : c'est le projet ouvert par défaut dans l'admin et l'espace client. */
    public function scopeNewestFirst(Builder $query): void
    {
        $query->latest()->orderByDesc('id');
    }

    public function statusLabel(): string
    {
        return config('portal.project_statuses')[$this->status] ?? $this->status;
    }

    /**
     * Forme attendue par le front (immo-front/src/lib/portal-types.ts, type Project).
     *
     * @return array{id: string, name: string, status: string, summary: string}
     */
    public function toPortalArray(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'summary' => (string) $this->summary,
        ];
    }
}
