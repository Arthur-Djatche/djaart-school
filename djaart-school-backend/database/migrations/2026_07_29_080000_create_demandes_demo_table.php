<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prospects de la landing page (formulaire "Demander une démo") : table
 * volontairement hors multi-tenant (pas de etablissement_id) puisqu'aucun
 * etablissement n'existe encore pour ces contacts — c'est justement ce que
 * la demande de demo precede.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_demo', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('email');
            $table->string('telephone')->nullable();
            $table->string('nom_etablissement');
            $table->unsignedInteger('effectif_estime')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_demo');
    }
};
