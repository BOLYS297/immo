<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Projets d'un client : chaque élément du portail (tâche, jalon, livrable, document, ticket) appartient à un projet.
     * Les éléments existants sont rattachés à un premier projet « Projet principal » par client.
     */
    public function up(): void
    {
        Schema::create('client_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // le client
            $table->string('name', 120);
            $table->string('status', 20)->default('active'); // clé de config('portal.project_statuses')
            $table->string('summary', 300)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::table('portal_items', function (Blueprint $table) {
            $table->foreignId('client_project_id')->nullable()->after('user_id')->constrained('client_projects')->cascadeOnDelete();
        });

        $now = now();
        DB::table('portal_items')->distinct()->pluck('user_id')->each(function ($userId) use ($now) {
            $projectId = DB::table('client_projects')->insertGetId([
                'user_id' => $userId, 'name' => 'Projet principal', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('portal_items')->where('user_id', $userId)->update(['client_project_id' => $projectId]);
        });
    }

    public function down(): void
    {
        Schema::table('portal_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_project_id');
        });

        Schema::dropIfExists('client_projects');
    }
};
