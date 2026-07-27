<?php

namespace App\Services;

use App\Models\Apprenant;
use App\Models\Etablissement;
use Illuminate\Support\Facades\DB;

class ApprenantService
{
    /**
     * Format sur 10 caracteres exactement : 2 lettres majuscules (code
     * etablissement) + 8 chiffres, soit une capacite de 99 999 999
     * apprenants par etablissement (le sigle peut etre de longueur
     * quelconque ou absent, contrairement a l'ancien format "{sigle}-00001"
     * qui ne respectait ni la longueur max ni la capacite requises).
     */
    public function generateMatricule(Etablissement $etablissement): string
    {
        return DB::transaction(function () use ($etablissement) {
            $locked = Etablissement::where('id', $etablissement->id)->lockForUpdate()->first();
            $sequence = $locked->next_matricule_sequence;

            $locked->update(['next_matricule_sequence' => $sequence + 1]);

            if ($sequence > 99_999_999) {
                throw new \RuntimeException("Capacite de matricules epuisee pour l'etablissement #{$etablissement->id}.");
            }

            $code = strtoupper(preg_replace('/[^A-Za-z]/', '', $locked->sigle ?? ''));
            $code = str_pad(substr($code, 0, 2), 2, 'X');

            return $code.str_pad((string) $sequence, 8, '0', STR_PAD_LEFT);
        });
    }

    public function findOrCreate(array $data, Etablissement $etablissement): Apprenant
    {
        if (! empty($data['apprenant_id'])) {
            return Apprenant::where('id', $data['apprenant_id'])
                ->where('etablissement_id', $etablissement->id)
                ->firstOrFail();
        }

        return Apprenant::create([
            ...$data['apprenant'],
            'etablissement_id' => $etablissement->id,
            'matricule' => $this->generateMatricule($etablissement),
        ]);
    }
}
