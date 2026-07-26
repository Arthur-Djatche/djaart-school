<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paiement_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('numero_recu');
            $table->string('fichier_pdf');
            $table->timestamps();

            $table->index('etablissement_id');
            $table->unique(['etablissement_id', 'numero_recu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recus');
    }
};
