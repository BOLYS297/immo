<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Un réglage du site ; clés et types déclarés dans config('cms.settings'). Lire via App\Services\SettingService. */
class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'json', // chaîne, ou traductions [locale => chaîne]
        ];
    }
}
