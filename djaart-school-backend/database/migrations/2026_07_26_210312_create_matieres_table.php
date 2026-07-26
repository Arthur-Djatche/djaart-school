<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('niveau_id')->constrained()->cascadeOnDelete();
            $table->string('nom');
            $table->decimal('coefficient', 4, 2)->default(1);
            $table->unsignedSmallInteger('credits_ects')->nullable();
            $table->timestamps();

            $table->index('etablissement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matieres');
    }
};
