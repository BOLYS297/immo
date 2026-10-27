<?php

namespace App\Services;

/**
 * Règles de validation et nettoyage du contenu d'une section, déduits du schéma `fields`
 * déclaré dans config/cms.php. Les champs inconnus du schéma sont ignorés.
 */
class BlockSchema
{
    private const MAX = ['text' => 255, 'textarea' => 2000, 'richtext' => 5000, 'link' => 255];

    // Chemin interne (« /contact », « /services?domaine=ia#chiffrage ») ou URL absolue http(s).
    public const LINK = 'regex:/^(\/(?!\/)|https?:\/\/)\S*$/i';

    public static function rules(array $fields): array
    {
        $rules = [];
        foreach (array_keys(config('cms.locales')) as $locale) {
            $rules["content.{$locale}"] = ['nullable', 'array'];
            $rules += static::fieldRules($fields, "content.{$locale}");
        }

        return $rules;
    }

    /** Noms lisibles pour les messages d'erreur : « Titre (EN) », « Lignes n°2 — Lien (FR) ». */
    public static function attributes(array $fields): array
    {
        $names = [];
        foreach (array_keys(config('cms.locales')) as $locale) {
            $suffix = ' ('.strtoupper($locale).')';
            foreach ($fields as $key => $field) {
                $names["content.{$locale}.{$key}"] = $field['label'].$suffix;
                foreach ($field['fields'] ?? [] as $sub => $subField) {
                    $names["content.{$locale}.{$key}.*.{$sub}"] = $field['item_label'].' — '.$subField['label'].$suffix;
                }
            }
        }

        return $names;
    }

    /** Ne garde que les champs du schéma ; les éléments de liste entièrement vides sont retirés. */
    public static function clean(array $fields, array $input): array
    {
        $content = [];
        foreach (array_keys(config('cms.locales')) as $locale) {
            $content[$locale] = static::cleanFields($fields, $input[$locale] ?? []);
        }

        return $content;
    }

    /** Réglages de présentation (listes de choix, communs aux langues). */
    public static function settingsRules(array $settings): array
    {
        $rules = ['settings' => ['nullable', 'array']];
        foreach ($settings as $key => $setting) {
            $rules["settings.{$key}"] = ($setting['type'] ?? 'select') === 'image'
                ? ['nullable', 'integer', 'exists:media,id']
                : ['nullable', 'in:'.implode(',', array_map('strval', array_keys($setting['options'])))];
        }

        return $rules;
    }

    public static function settingsAttributes(array $settings): array
    {
        return collect($settings)->mapWithKeys(fn (array $setting, string $key) => ["settings.{$key}" => $setting['label']])->all();
    }

    /** Valeur choisie pour chaque réglage du schéma, sinon sa valeur par défaut. */
    public static function cleanSettings(array $settings, mixed $input): array
    {
        $input = is_array($input) ? $input : [];

        return collect($settings)->mapWithKeys(function (array $setting, string $key) use ($input) {
            $value = (string) ($input[$key] ?? '');
            if (($setting['type'] ?? 'select') === 'image') {
                return [$key => ctype_digit($value) ? $value : null]; // vide = pas d'image
            }

            return [$key => array_key_exists($value, $setting['options']) ? $value : (string) $setting['default']];
        })->all();
    }

    private static function fieldRules(array $fields, string $prefix): array
    {
        $rules = [];
        foreach ($fields as $key => $field) {
            $path = "{$prefix}.{$key}";
            if ($field['type'] === 'repeater') {
                $rules[$path] = ['nullable', 'array', 'max:'.($field['max'] ?? 12)];
                $rules += static::fieldRules($field['fields'], "{$path}.*");

                continue;
            }
            if ($field['type'] === 'image') {
                $rules[$path] = ['nullable', 'integer', 'exists:media,id'];

                continue;
            }
            $rules[$path] = array_filter(['nullable', 'string', 'max:'.self::MAX[$field['type']], $field['type'] === 'link' ? self::LINK : null]);
        }

        return $rules;
    }

    private static function cleanFields(array $fields, mixed $input): array
    {
        $input = is_array($input) ? $input : [];
        $out = [];
        foreach ($fields as $key => $field) {
            $value = $input[$key] ?? null;
            if ($field['type'] === 'repeater') {
                $items = array_map(fn ($item) => static::cleanFields($field['fields'], $item), is_array($value) ? array_values($value) : []);
                $out[$key] = array_values(array_filter($items, fn (array $item) => array_filter($item, fn ($v) => $v !== '') !== []));

                continue;
            }
            // Image : identifiant du média, en texte comme les autres valeurs du contenu.
            $out[$key] = $field['type'] === 'image'
                ? (ctype_digit((string) $value) ? (string) $value : '')
                : (is_string($value) ? trim($value) : '');
        }

        return $out;
    }

    /** Identifiants de médias présents dans un contenu (une langue), d'après les champs « image » du schéma. */
    public static function imageIds(array $fields, array $content): array
    {
        $ids = [];
        static::walkImages($fields, $content, function (string $id) use (&$ids) {
            $ids[] = $id;

            return $id;
        });

        return $ids;
    }

    /** Remplace chaque identifiant de média par le résultat de $map (null retire le champ : image supprimée). */
    public static function mapImages(array $fields, array $content, callable $map): array
    {
        return static::walkImages($fields, $content, $map);
    }

    private static function walkImages(array $fields, array $content, callable $map): array
    {
        foreach ($fields as $key => $field) {
            if (! array_key_exists($key, $content)) {
                continue;
            }
            if ($field['type'] === 'repeater' && is_array($content[$key])) {
                $content[$key] = array_map(fn ($item) => is_array($item) ? static::walkImages($field['fields'], $item, $map) : $item, $content[$key]);
            } elseif ($field['type'] === 'image' && is_string($content[$key]) && $content[$key] !== '') {
                $value = $map($content[$key]);
                if ($value === null) {
                    unset($content[$key]);
                } else {
                    $content[$key] = $value;
                }
            }
        }

        return $content;
    }
}
