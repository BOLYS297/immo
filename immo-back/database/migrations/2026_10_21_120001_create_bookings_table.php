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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->index();
            $table->string('company');
            $table->string('need', 20); // ia, web, infra
            $table->string('deadline', 20); // urgent, month, later
            $table->dateTime('slot'); // UTC
            // Rempli tant que le rendez-vous est actif, vidé à l'annulation : un créneau ne peut être réservé
            // qu'une fois (MySQL accepte plusieurs NULL dans un index unique).
            $table->string('slot_key', 20)->nullable()->unique();
            $table->string('status', 20)->default('confirme'); // confirme, honore, annule
            $table->string('locale', 5)->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->index('slot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
