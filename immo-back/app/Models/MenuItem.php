<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Services\FrontRevalidator;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/** Lien (ou colonne du pied de page) d'un emplacement de menu déclaré dans config('cms.menus'). */
class MenuItem extends Model
{
    use HasTranslations, RecordsActivity;

    protected $fillable = ['menu', 'parent_id', 'label', 'url', 'display', 'new_tab', 'sort_order'];

    public array $translatable = ['label'];

    protected function casts(): array
    {
        return [
            'new_tab' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => FrontRevalidator::tags('site'));
        static::deleted(fn () => FrontRevalidator::tags('site'));
    }

    public function children()
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function parent()
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /** Éléments de premier niveau d'un emplacement, avec leurs liens enfants. */
    public static function tree(string $menu)
    {
        return static::where('menu', $menu)->whereNull('parent_id')->with('children')->orderBy('sort_order')->orderBy('id')->get();
    }

    /** Éléments au même niveau (même emplacement, même parent), dans l'ordre d'affichage. */
    public function siblings()
    {
        return static::where('menu', $this->menu)->where('parent_id', $this->parent_id)->orderBy('sort_order')->orderBy('id');
    }
}
