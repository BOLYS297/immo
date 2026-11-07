<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Boutons uniques des sections → listes « buttons » ; lien unique d'un membre d'équipe → liste « socials ».
 * Convertit les brouillons (page_sections) et toutes les versions publiées ou archivées (page_revisions).
 * Un libellé ou un lien laissé vide en anglais reprend celui de la version française, comme avant.
 */
return new class extends Migration
{
    /** Liens par défaut du front quand le champ était vide. */
    private const DEFAULT_HREFS = ['commitments.button' => '/contact', 'cta_band.primary' => '/contact', 'cta_band.secondary' => '/services'];

    public function up(): void
    {
        DB::table('page_sections')->orderBy('id')->each(function (object $row) {
            $content = json_decode((string) $row->content, true);
            if (is_array($content) && ($converted = $this->convertLocales($row->type, $content)) !== $content) {
                DB::table('page_sections')->where('id', $row->id)->update(['content' => json_encode($converted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            }
        });

        DB::table('page_revisions')->orderBy('id')->each(function (object $row) {
            $snapshot = json_decode((string) $row->snapshot, true);
            if (! is_array($snapshot) || ! is_array($snapshot['sections'] ?? null)) {
                return;
            }
            $converted = $snapshot;
            foreach ($converted['sections'] as $i => $section) {
                if (is_array($section['content'] ?? null)) {
                    $converted['sections'][$i]['content'] = $this->convertLocales((string) ($section['type'] ?? ''), $section['content']);
                }
            }
            if ($converted !== $snapshot) {
                DB::table('page_revisions')->where('id', $row->id)->update(['snapshot' => json_encode($converted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            }
        });
    }

    public function down(): void
    {
        // Conversion sans retour : les anciens champs ne sont plus lus par l'admin.
    }

    /** @param  array<string, mixed>  $content  {"fr": {...}, "en": {...}} */
    private function convertLocales(string $type, array $content): array
    {
        $default = config('cms.default_locale', 'fr');
        $fallback = is_array($content[$default] ?? null) ? $content[$default] : [];
        foreach ($content as $locale => $values) {
            if (is_array($values)) {
                $content[$locale] = $this->convert($type, $values, $locale === $default ? [] : $fallback);
            }
        }

        return $content;
    }

    private function convert(string $type, array $c, array $fallback): array
    {
        return match ($type) {
            'media_text', 'callout' => $this->toButton($c, $fallback, $type, 'link_label', 'href'),
            'commitments' => $this->toButton($c, $fallback, $type, 'button', 'href'),
            'cta_band' => $this->toButton($this->toButton($c, $fallback, $type, 'primary', 'primary_href'), $fallback, $type, 'secondary', 'secondary_href'),
            // Éléments de liste : jamais complétés par la version française (le front ne le faisait pas).
            'cards' => $this->eachItem($c, 'items', fn (array $item) => $this->toButton($item, [], $type, 'link_label', 'href')),
            'service_domains' => $this->eachItem($c, 'domains', fn (array $item) => $this->toButton($item, [], $type, 'cta', 'href')),
            'team_members' => $this->eachItem($c, 'members', fn (array $member) => $this->toSocial($member)),
            default => $c,
        };
    }

    private function toButton(array $c, array $fallback, string $type, string $labelKey, string $hrefKey): array
    {
        if (! array_key_exists($labelKey, $c) && ! array_key_exists($hrefKey, $c)) {
            return $c;
        }
        $label = trim((string) ($c[$labelKey] ?? ''));
        $href = trim((string) ($c[$hrefKey] ?? ''));
        unset($c[$labelKey], $c[$hrefKey]);

        if ($label === '' && $href === '') {
            return $c; // rien en anglais : la liste française s'applique
        }
        $label = $label !== '' ? $label : trim((string) ($fallback[$labelKey] ?? ''));
        $href = $href !== '' ? $href : (trim((string) ($fallback[$hrefKey] ?? '')) ?: (self::DEFAULT_HREFS["{$type}.{$labelKey}"] ?? ''));
        if ($href !== '') {
            $c['buttons'] = [...(is_array($c['buttons'] ?? null) ? $c['buttons'] : []), ['label' => $label, 'href' => $href]];
        }

        return $c;
    }

    private function eachItem(array $c, string $key, callable $convert): array
    {
        if (is_array($c[$key] ?? null)) {
            $c[$key] = array_map(fn ($item) => is_array($item) ? $convert($item) : $item, $c[$key]);
        }

        return $c;
    }

    private function toSocial(array $member): array
    {
        if (! array_key_exists('link', $member)) {
            return $member;
        }
        $url = trim((string) $member['link']);
        unset($member['link']);
        if ($url !== '') {
            $member['socials'] = [['network' => $this->network($url), 'url' => $url]];
        }

        return $member;
    }

    private function network(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $networks = [
            'linkedin' => ['linkedin.com'], 'x' => ['x.com', 'twitter.com'], 'facebook' => ['facebook.com', 'fb.com'],
            'instagram' => ['instagram.com'], 'tiktok' => ['tiktok.com'], 'youtube' => ['youtube.com', 'youtu.be'],
            'github' => ['github.com'], 'dribbble' => ['dribbble.com'], 'whatsapp' => ['wa.me', 'whatsapp.com'],
        ];
        foreach ($networks as $network => $domains) {
            foreach ($domains as $domain) {
                if ($host === $domain || str_ends_with($host, ".{$domain}")) {
                    return $network;
                }
            }
        }

        return 'website';
    }
};
