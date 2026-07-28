<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inscription_id')->constrained()->cascadeOnDelete();
            // Nullable : un paiement n'est plus rattache a une tranche precise (cf.
            // correctif encaissement flexible, EcheancierService) - un encaissement
            // peut couvrir plusieurs tranches ou une seule partiellement, plafonne
            // uniquement par le solde de la pension totale. Conserve pour reference
            // historique eventuelle, mais toujours NULL pour les nouveaux paiements.
            $table->foreignId('tranche_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('montant', 10, 2);
            $table->enum('mode_paiement', ['especes', 'mobile_money', 'virement', 'cheque']);
            $table->foreignId('caissier_id')->constrained('users')->cascadeOnDelete();
            $table->date('date_paiement');
            $table->timestamps();

            $table->index('etablissement_id');
            $table->index('tranche_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
