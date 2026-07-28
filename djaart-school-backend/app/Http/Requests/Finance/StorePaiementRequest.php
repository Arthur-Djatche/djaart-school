<?php

namespace App\Http\Requests\Finance;

use App\Models\Paiement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'montant' => ['required', 'numeric', 'min:0.01'],
            'mode_paiement' => ['required', Rule::in(['especes', 'mobile_money', 'virement', 'cheque'])],
        ];
    }
}
