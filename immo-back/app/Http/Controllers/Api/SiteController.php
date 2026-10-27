<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    /** GET /api/v1/site?locale=fr — réglages globaux et menus du front (en-tête, pied de page). */
    public function show(Request $request): JsonResponse
    {
        $locale = $request->query('locale');
        $locale = is_string($locale) && array_key_exists($locale, config('cms.locales')) ? $locale : config('cms.default_locale');

        $link = fn (MenuItem $item) => [
            'label' => $item->getTranslation('label', $locale),
            'url' => $item->url,
            'newTab' => $item->new_tab,
            'display' => $item->display,
        ];

        $menus = collect(config('cms.menus'))->map(fn (array $config, string $key) => MenuItem::tree($key)
            ->map(fn (MenuItem $item) => $config['depth'] >= 2
                ? ['label' => $item->getTranslation('label', $locale), 'links' => $item->children->map($link)->values()]
                : $link($item))
            ->values());

        return response()->json(['data' => ['settings' => SettingService::resolved($locale), 'menus' => $menus]]);
    }
}
