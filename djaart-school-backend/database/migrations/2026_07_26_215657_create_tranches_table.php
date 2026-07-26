<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tranches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('frais_scolarite_id')->constrained('frais_scolarite')->cascadeOnDelete();
            $table->unsignedTinyInteger('numero');
            $table->decimal('montant', 10, 2);
            $table->date('date_echeance');
            $table->timestamps();

            $table->index('etablissement_id');
            $table->unique(['frais_scolarite_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tranches');
    }
};
