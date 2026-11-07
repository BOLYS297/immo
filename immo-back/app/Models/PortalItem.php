<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Services\Poles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PortalItem extends Model
{
    use RecordsActivity;

    /** Disque privé des fichiers joints (documents) : jamais servis publiquement. */
    public const FILE_DISK = 'local';

    protected $fillable = [
        'user_id',
        'client_project_id',
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

    protected static function booted(): void
    {
        static::deleted(fn (PortalItem $item) => $item->deleteFile());
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }

    /** Définition du type (config/portal.php), listes de pôles résolues. */
    public function definition(): ?array
    {
        $definition = config("portal.types.{$this->type}");
        if ($definition) {
            $definition['fields'] = array_map([Poles::class, 'resolve'], $definition['fields']);
        }

        return $definition;
    }

    /** Valeur d'un champ, ou sa valeur par défaut du schéma. */
    public function field(string $key): mixed
    {
        return $this->data[$key] ?? ($this->definition()['fields'][$key]['default'] ?? null);
    }

    /**
     * Fichier joint enregistré sur le disque privé, ou null.
     *
     * @return array{path: string, name: string}|null
     */
    public function file(): ?array
    {
        $file = $this->data['file'] ?? null;

        return is_array($file) && isset($file['path'], $file['name']) ? $file : null;
    }

    public function deleteFile(): void
    {
        if ($path = $this->file()['path'] ?? null) {
            Storage::disk(self::FILE_DISK)->delete($path);
        }
    }

    /**
     * Forme attendue par le front (immo-front/src/lib/portal-types.ts, type Item).
     * Le chemin du fichier joint reste côté serveur : le front ne reçoit que son nom.
     * Tâche : `poleLabel` = titre du service (pôle) dans la langue demandée.
     *
     * @return array<string, mixed>
     */
    public function toPortalArray(?string $locale = null): array
    {
        $data = $this->data ?? [];
        unset($data['file']);
        $file = $this->file();

        return [
            'id' => (string) $this->id,
            'clientId' => (string) $this->user_id,
            'projectId' => (string) $this->client_project_id,
            'createdAt' => $this->created_at?->toIso8601String(),
            'type' => $this->type,
            'title' => $this->title,
        ] + $data
            + ($this->type === 'document' ? ['file' => $file ? ['name' => $file['name']] : null] : [])
            + ($this->type === 'task' ? ['poleLabel' => (string) Poles::label($data['pole'] ?? null, $locale)] : []);
    }
}
