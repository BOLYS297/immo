<?php

namespace App\Services;

use Illuminate\Validation\Rule;

/**
 * Validation et typage des champs d'un élément du portail, déduits de config/portal.php.
 * Les valeurs sont enregistrées avec le type attendu par le front (entiers pour col, progress, priority, amount).
 */
class PortalItemSchema
{
    public static function fields(string $type): array
    {
        return config("portal.types.{$type}.fields") ?? abort(404);
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
            };
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
}
