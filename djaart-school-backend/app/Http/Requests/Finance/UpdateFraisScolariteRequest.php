<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateFraisScolariteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('fraisScolarite'));
    }

    public function rules(): array
    {
        return [
            'montant_total' => ['required', 'numeric', 'min:0.01'],
            'mode' => ['required', Rule::in(['comptant', 'tranches'])],
            'tranches' => ['required_if:mode,tranches', 'array', 'min:1'],
            'tranches.*.numero' => ['required_with:tranches', 'integer', 'min:1'],
            'tranches.*.montant' => ['required_with:tranches', 'numeric', 'min:0.01'],
            'tranches.*.date_echeance' => ['required_with:tranches', 'date'],
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
