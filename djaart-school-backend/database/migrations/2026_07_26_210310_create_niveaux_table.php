<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('niveaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('filiere_id')->constrained()->cascadeOnDelete();
            $table->string('libelle');
            $table->unsignedSmallInteger('ordre')->default(1);
            $table->enum('type_systeme', ['classique', 'lmd']);
            $table->timestamps();

            $table->index('etablissement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('niveaux');
    }
};
