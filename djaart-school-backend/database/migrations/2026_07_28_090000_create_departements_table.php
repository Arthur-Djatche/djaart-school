<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->string('nom');
            $table->string('code');
            $table->foreignId('chef_departement_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('etablissement_id');
            $table->unique(['etablissement_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departements');
    }
};
