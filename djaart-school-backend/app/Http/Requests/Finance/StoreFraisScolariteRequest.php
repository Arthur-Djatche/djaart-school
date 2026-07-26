<?php

namespace App\Http\Requests\Finance;

use App\Models\FraisScolarite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFraisScolariteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', FraisScolarite::class);
    }

    public function rules(): array
    {
        $etablissementId = $this->user()->hasRole('super_admin')
            ? $this->input('etablissement_id')
            : $this->user()->etablissement_id;

        return [
            'niveau_id' => [
                'required',
                Rule::exists('niveaux', 'id')->where('etablissement_id', $etablissementId),
                Rule::unique('frais_scolarite', 'niveau_id')->where('annee_academique_id', $this->input('annee_academique_id')),
            ],
            'annee_academique_id' => [
                'required',
                Rule::exists('annees_academiques', 'id')->where('etablissement_id', $etablissementId),
            ],
            'montant_total' => ['required', 'numeric', 'min:0.01'],
            'mode' => ['required', Rule::in(['comptant', 'tranches'])],
            'tranches' => ['required_if:mode,tranches', 'array', 'min:1'],
            'tranches.*.numero' => ['required_with:tranches', 'integer', 'min:1'],
            'tranches.*.montant' => ['required_with:tranches', 'numeric', 'min:0.01'],
            'tranches.*.date_echeance' => ['required_with:tranches', 'date'],
            'etablissement_id' => $this->user()->hasRole('super_admin')
                ? ['required', 'exists:etablissements,id']
                : ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('mode') !== 'tranches') {
                return;
            }

            $tranches = collect($this->input('tranches', []));
            $sommeTranches = $tranches->sum(fn ($tranche) => (float) ($tranche['montant'] ?? 0));
            $montantTotal = (float) $this->input('montant_total', 0);

            if (abs($sommeTranches - $montantTotal) > 0.01) {
                $validator->errors()->add(
                    'tranches',
                    "La somme des tranches ({$sommeTranches}) doit être égale au montant total ({$montantTotal})."
                );
            }
        });
    }
}
