<?php

namespace App\Http\Requests\Parametrage;

use App\Models\Etablissement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEtablissementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Etablissement::class);
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'type_etablissement' => ['required', Rule::in(['primaire', 'secondaire', 'universitaire', 'centre_formation'])],
            // Un etablissement peut cumuler jusqu'a 2 types (ex. secondaire +
            // centre de formation) ; le second est optionnel et doit differer du premier.
            'type_etablissement_secondaire' => [
                'nullable',
                Rule::in(['primaire', 'secondaire', 'universitaire', 'centre_formation']),
                Rule::notIn([$this->input('type_etablissement')]),
            ],
            'sigle' => ['nullable', 'string', 'max:20'],
            'adresse' => ['nullable', 'string', 'max:255'],
        ];
    }
}
