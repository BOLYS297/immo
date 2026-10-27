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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_category_id')->constrained('project_categories')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('client_name')->nullable();
            $table->text('short_description');
            $table->text('problem_statement');
            $table->text('solution_statement');
            $table->json('technologies')->nullable();
            $table->text('verified_results')->nullable();
            $table->string('main_image')->nullable();
            $table->string('demo_url')->nullable();
            $table->date('completion_date')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
