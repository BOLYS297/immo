<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

/**
 * Réglages globaux du site, décrits par config('cms.settings').
 * Une valeur non enregistrée (ou vide) reprend la langue par défaut, puis le `default` du registre.
 */
class SettingService
{
    private const CACHE_KEY = 'cms.settings';

    private const MAX = ['select' => 40, 'text' => 255, 'textarea' => 1000, 'email' => 255, 'url' => 255, 'link' => 255, 'phone' => 30, 'lines' => 500];

    /** Champs à plat : clé => définition. */
    public static function fields(): array
    {
        return collect(config('cms.settings'))->flatMap(fn (array $group) => $group['fields'])->all();
    }

    /** Valeurs enregistrées : clé => chaîne ou [locale => chaîne]. */
    public static function stored(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => SiteSetting::pluck('value', 'key')->all());
    }

    /**
     * Valeur d'un réglage dans une langue : traduction vide = langue par défaut. Le `default` du registre ne sert
     * que tant que le réglage n'a jamais été enregistré : un champ vidé dans l'admin reste vide (null).
     */
    public static function get(string $key, ?string $locale = null): ?string
    {
        $field = static::fields()[$key] ?? null;
        if (! $field) {
            return null;
        }
        $default = config('cms.default_locale');
        $locale ??= $default;
        $stored = static::stored();
        $value = array_key_exists($key, $stored) ? $stored[$key] : ($field['default'] ?? null);
        $value = is_array($value) ? (filled($value[$locale] ?? null) ? $value[$locale] : ($value[$default] ?? null)) : $value;

        return filled($value) ? $value : null;
    }

    /** Tous les réglages résolus pour une langue (contrat de l'API du front). */
    public static function resolved(string $locale): array
    {
        return collect(static::fields())->keys()->mapWithKeys(fn (string $key) => [$key => static::get($key, $locale)])->all();
    }

    /** Valeur saisie dans le formulaire : enregistrée, sinon valeur par défaut. */
    public static function formValue(string $key, ?string $locale = null): ?string
    {
        $field = static::fields()[$key];
        $value = static::stored()[$key] ?? $field['default'] ?? null;

        return is_array($value) ? ($value[$locale] ?? null) : $value;
    }

    public static function rules(): array
    {
        $rules = [];
        foreach (static::fields() as $key => $field) {
            $rule = ['nullable', 'string', 'max:'.self::MAX[$field['type']], ...match ($field['type']) {
                'email' => ['email:rfc'],
                'url' => ['url:http,https'],
                'link' => [BlockSchema::LINK],
                'phone' => ['regex:/^\+?[0-9 ().-]{6,30}$/'],
                'select' => [Rule::in(array_keys($field['options']))],
                default => [],
            }];
            if ($field['translatable'] ?? false) {
                foreach (array_keys(config('cms.locales')) as $locale) {
                    $rules["settings.{$key}.{$locale}"] = $rule;
                }
            } else {
                $rules["settings.{$key}"] = $rule;
            }
        }

        return $rules;
    }

    public static function attributes(): array
    {
        $names = [];
        foreach (static::fields() as $key => $field) {
            $names["settings.{$key}"] = $field['label'];
            foreach (array_keys(config('cms.locales')) as $locale) {
                $names["settings.{$key}.{$locale}"] = $field['label'].' ('.strtoupper($locale).')';
            }
        }

        return $names;
    }

    /** Enregistre les valeurs validées ; toutes les langues sont écrites pour qu'un champ vidé le soit vraiment. */
    public static function save(array $input): void
    {
        $before = static::stored();
        $changed = [];
        foreach (static::fields() as $key => $field) {
            $clean = function ($value) use ($field) {
                $value = trim(str_replace("\r\n", "\n", (string) $value));
                if ($field['type'] === 'lines') {
                    $value = implode("\n", array_slice(array_values(array_filter(array_map('trim', explode("\n", $value)), 'strlen')), 0, $field['max'] ?? 10));
                }

                return $value;
            };
            $value = ($field['translatable'] ?? false)
                ? collect(array_keys(config('cms.locales')))->mapWithKeys(fn ($l) => [$l => $clean($input[$key][$l] ?? '')])->all()
                : $clean($input[$key] ?? '');

            if (($before[$key] ?? null) !== $value) {
                $changed[] = $key;
            }
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        if ($changed) {
            Activity::record('settings_updated', null, ['fields' => $changed]);
        }

        static::clearCache();
        FrontRevalidator::tags('site');
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
