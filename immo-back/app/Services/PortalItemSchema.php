<?php

namespace App\Services;

use App\Models\PortalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validation et typage des champs d'un élément du portail, déduits de config/portal.php.
 * Les valeurs sont enregistrées avec le type attendu par le front (entiers pour col, progress, priority, amount).
 * Les champs « file » sont traités à part (files()) : fichier rangé sur le disque privé, jamais exposé par son chemin.
 */
class PortalItemSchema
{
    /** Champs du type, listes de pôles résolues (App\Services\Poles). */
    public static function fields(string $type): array
    {
        return array_map([Poles::class, 'resolve'], config("portal.types.{$type}.fields") ?? abort(404));
    }

    public static function rules(string $type): array
    {
        $rules = ['title' => ['required', 'string', 'max:120']];
        foreach (static::fields($type) as $key => $field) {
            $rules["data.{$key}"] = match ($field['type']) {
                'text', 'textarea' => ['nullable', 'string', 'max:'.$field['max']],
                'url' => ['nullable', 'url:http,https', 'max:'.$field['max']],
                'integer' => ['nullable', 'integer', 'min:'.$field['min'], 'max:'.$field['max']],
                'amount' => ['nullable', 'integer', 'min:0', 'max:9999999999'],
                'select' => ['nullable', Rule::in(array_map('strval', array_keys($field['options'])))],
                'file' => ['nullable', 'file', 'mimes:'.implode(',', $field['mimes']), 'max:'.$field['max_kb']],
            };
            if ($field['type'] === 'file') {
                $rules["remove.{$key}"] = ['nullable', 'boolean'];
            }
        }

        return $rules;
    }

    public static function attributes(string $type): array
    {
        $names = ['title' => 'Titre'];
        foreach (static::fields($type) as $key => $field) {
            $names["data.{$key}"] = $field['label'];
        }

        return $names;
    }

    /** @return array<string, mixed> */
    public static function clean(string $type, array $input): array
    {
        $data = [];
        foreach (static::fields($type) as $key => $field) {
            if ($field['type'] === 'file') {
                continue;
            }
            $value = $input[$key] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $data[$key] = match ($field['type']) {
                'text', 'textarea', 'url' => (string) ($value ?? ''),
                'integer' => ($value === null || $value === '') ? $field['default'] : (int) $value,
                'amount' => ($value === null || $value === '') ? null : (int) $value,
                'select' => ($value === null || $value === '') ? $field['default'] : (($field['int'] ?? false) ? (int) $value : (string) $value),
            };
        }

        return $data;
    }

    /**
     * Fichiers joints après envoi du formulaire (à appeler après la validation) : un nouveau fichier remplace
     * l'actuel, la case « Retirer » le supprime, sinon l'actuel est conservé.
     *
     * @return array<string, array{path: string, name: string}>
     */
    public static function files(string $type, Request $request, int $clientId, ?PortalItem $item = null): array
    {
        $files = [];
        foreach (static::fields($type) as $key => $field) {
            if ($field['type'] !== 'file') {
                continue;
            }
            $current = $item?->data[$key] ?? null;
            $upload = $request->file("data.{$key}");
            if ($upload || $request->boolean("remove.{$key}")) {
                if (is_array($current) && isset($current['path'])) {
                    Storage::disk(PortalItem::FILE_DISK)->delete($current['path']);
                }
                $current = $upload ? [
                    'path' => $upload->store("portal/{$clientId}", PortalItem::FILE_DISK),
                    'name' => Str::limit(basename($upload->getClientOriginalName()), 150, ''),
                ] : null;
            }
            if (is_array($current)) {
                $files[$key] = $current;
            }
        }

        return $files;
    }
}
