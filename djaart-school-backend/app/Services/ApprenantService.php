<?php

namespace App\Services;

use App\Models\Apprenant;
use App\Models\Etablissement;
use Illuminate\Support\Facades\DB;

class ApprenantService
{
    public function generateMatricule(Etablissement $etablissement): string
    {
        return DB::transaction(function () use ($etablissement) {
            $locked = Etablissement::where('id', $etablissement->id)->lockForUpdate()->first();
            $sequence = $locked->next_matricule_sequence;

            $locked->update(['next_matricule_sequence' => $sequence + 1]);

            $prefixe = $locked->sigle ?: 'ETB';

            return sprintf('%s-%05d', $prefixe, $sequence);
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
