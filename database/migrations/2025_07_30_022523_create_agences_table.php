<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nom_agence');
            $table->text('description')->nullable();
            $table->string('adresse');
            $table->string('ville');
            $table->string('commune');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->json('horaires'); // {lundi: "8h-18h", mardi: "fermé", ...}
            $table->json('photos')->nullable();
            $table->decimal('zone_couverture_km', 5, 2)->default(10.00);
            $table->boolean('actif')->default(true);
            $table->text('message_accueil')->nullable();
            $table->json('promotions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agences');
    }
};