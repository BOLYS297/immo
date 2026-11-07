<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Fonction et entreprise d'un témoignage saisies par langue : la valeur actuelle devient la version française. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->text('role')->nullable()->change();
            $table->text('company')->nullable()->change();
        });

        $locale = config('cms.default_locale', 'fr');
        DB::table('testimonials')->orderBy('id')->each(function (object $row) use ($locale) {
            $wrap = fn (?string $value) => filled($value) ? json_encode([$locale => $value], JSON_UNESCAPED_UNICODE) : null;
            DB::table('testimonials')->where('id', $row->id)->update(['role' => $wrap($row->role), 'company' => $wrap($row->company)]);
        });
    }

    public function down(): void
    {
        $locale = config('cms.default_locale', 'fr');
        DB::table('testimonials')->orderBy('id')->each(function (object $row) use ($locale) {
            $unwrap = fn (?string $value) => $value === null ? null : (json_decode($value, true)[$locale] ?? null);
            DB::table('testimonials')->where('id', $row->id)->update(['role' => $unwrap($row->role), 'company' => $unwrap($row->company)]);
        });

        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('role')->nullable()->change();
            $table->string('company')->nullable()->change();
        });
    }
};
