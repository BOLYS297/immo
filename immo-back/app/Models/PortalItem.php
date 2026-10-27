<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class PortalItem extends Model
{
    use RecordsActivity;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function definition(): ?array
    {
        return config("portal.types.{$this->type}");
    }

    /** Valeur d'un champ, ou sa valeur par défaut du schéma. */
    public function field(string $key): mixed
    {
        return $this->data[$key] ?? ($this->definition()['fields'][$key]['default'] ?? null);
    }

    /**
     * Forme attendue par le front (immo-front/src/lib/portal-types.ts, type Item).
     *
     * @return array<string, mixed>
     */
    public function toPortalArray(): array
    {
        return [
            'id' => (string) $this->id,
            'clientId' => (string) $this->user_id,
            'createdAt' => $this->created_at?->toIso8601String(),
            'type' => $this->type,
            'title' => $this->title,
        ] + ($this->data ?? []);
    }
}
