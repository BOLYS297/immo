<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** SEO des pages : image de partage (médiathèque) et exclusion des moteurs de recherche. Font partie du brouillon publié. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('og_image_id')->nullable()->after('meta_description')->constrained('media')->nullOnDelete();
            $table->boolean('noindex')->default(false)->after('og_image_id');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('og_image_id');
            $table->dropColumn('noindex');
        });
    }
};
