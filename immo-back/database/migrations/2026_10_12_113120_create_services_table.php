<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->constrained('service_categories')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description');
            $table->longText('detailed_content');
            $table->json('needs_addressed')->nullable();
            $table->json('client_benefits')->nullable();
            $table->json('deliverables')->nullable();
            $table->string('icon')->default('bi-laptop');
            $table->string('image')->nullable();
            $table->string('indicative_price')->nullable();
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
