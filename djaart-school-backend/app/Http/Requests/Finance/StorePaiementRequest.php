<?php

namespace App\Http\Requests\Finance;

use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\Tranche;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Paiement::class);
    }

    public function rules(): array
    {
        $etablissementId = $this->user()->hasRole('super_admin') ? null : $this->user()->etablissement_id;

        return [
            'inscription_id' => [
                'required',
                $etablissementId
                    ? Rule::exists('inscriptions', 'id')->where('etablissement_id', $etablissementId)
                    : Rule::exists('inscriptions', 'id'),
            ],
            'tranche_id' => [
                'required',
                $etablissementId
                    ? Rule::exists('tranches', 'id')->where('etablissement_id', $etablissementId)
                    : Rule::exists('tranches', 'id'),
            ],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'mode_paiement' => ['required', Rule::in(['especes', 'mobile_money', 'virement', 'cheque'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('inscription_id') || $validator->errors()->has('tranche_id')) {
                return;
            }

            $inscription = Inscription::find($this->input('inscription_id'));
            $tranche = Tranche::find($this->input('tranche_id'));

            if ($inscription && $tranche && $inscription->frais_scolarite_id !== $tranche->frais_scolarite_id) {
                $validator->errors()->add(
                    'tranche_id',
                    "Cette tranche n'appartient pas à la grille de frais de cette inscription."
                );
            }
        });
    }
}
