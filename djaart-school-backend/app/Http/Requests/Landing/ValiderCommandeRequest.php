<?php

namespace App\Http\Requests\Landing;

use App\Support\GrantablePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValiderCommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'type_etablissement' => ['required', Rule::in(['primaire', 'secondaire', 'universitaire', 'centre_formation'])],
            // Optionnel : si renseigne, un 2e etablissement distinct de ce type est
            // cree en plus du premier, avec le meme admin (cf. CommandeController::valider) —
            // pas un cumul de types sur un seul et meme etablissement.
            'type_etablissement_secondaire' => [
                'nullable',
                Rule::in(['primaire', 'secondaire', 'universitaire', 'centre_formation']),
                Rule::notIn([$this->input('type_etablissement')]),
            ],
            'duree_mois' => ['required', 'integer', 'min:1', 'max:60'],
            'permissions' => ['present', 'array'],
            'permissions.*' => [Rule::in(GrantablePermissions::cles())],
        ];
    }
}
