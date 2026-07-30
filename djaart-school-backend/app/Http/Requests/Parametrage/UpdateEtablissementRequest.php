<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEtablissementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('etablissement'));
    }

    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'type_etablissement' => [
                'sometimes',
                'required',
                Rule::in(['primaire', 'secondaire', 'universitaire', 'centre_formation']),
                // Le type d'etablissement conditionne les fonctionnalites incluses
                // (choisi par le super_admin a la validation de la commande) : un
                // admin_etablissement ne doit jamais pouvoir se le reattribuer.
                Rule::prohibitedIf(! $this->user()->hasRole('super_admin')),
            ],
            'type_etablissement_secondaire' => [
                'sometimes',
                'nullable',
                Rule::in(['primaire', 'secondaire', 'universitaire', 'centre_formation']),
                Rule::notIn([$this->input('type_etablissement', $this->route('etablissement')->type_etablissement)]),
                Rule::prohibitedIf(! $this->user()->hasRole('super_admin')),
            ],
            'sigle' => ['nullable', 'string', 'max:20'],
            'adresse' => ['nullable', 'string', 'max:255'],
        ];
    }
}
