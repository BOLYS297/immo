<?php

namespace App\Http\Controllers\Admin\Concerns;

/** Champs saisis par langue (config cms.locales) dans les formulaires de l'admin : champ[fr], champ[en]. */
trait TranslatedInput
{
    /**
     * Règles par langue : la langue par défaut reçoit $required (ex. ['required']), les autres sont facultatives.
     * $fields : nom => [règles communes], $required : noms obligatoires en langue par défaut.
     */
    protected function translatedRules(array $fields, array $required = []): array
    {
        $default = config('cms.default_locale');
        $rules = [];
        foreach ($fields as $name => $common) {
            foreach (array_keys(config('cms.locales')) as $locale) {
                $rules["{$name}.{$locale}"] = [$locale === $default && in_array($name, $required, true) ? 'required' : 'nullable', ...$common];
            }
        }

        return $rules;
    }

    /** Noms lisibles des champs par langue pour les messages d'erreur : « titre (EN) ». */
    protected function translatedAttributes(array $labels): array
    {
        $names = [];
        foreach ($labels as $name => $label) {
            foreach (array_keys(config('cms.locales')) as $locale) {
                $names["{$name}.{$locale}"] = $label.' ('.strtoupper($locale).')';
            }
        }

        return $names;
    }

    /**
     * Toutes les langues, '' pour un champ vide : spatie le traite comme absent, et vider un champ dans l'admin
     * efface bien l'ancienne valeur. $lines : une valeur par ligne (liste).
     */
    protected function translations(array $validated, string $name, bool $lines = false): array
    {
        return collect(array_keys(config('cms.locales')))->mapWithKeys(function (string $locale) use ($validated, $name, $lines) {
            $value = trim(str_replace("\r\n", "\n", (string) ($validated[$name][$locale] ?? '')));

            return [$locale => $lines ? array_values(array_filter(array_map('trim', explode("\n", $value)), 'strlen')) : $value];
        })->all();
    }
}
