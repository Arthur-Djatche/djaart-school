<?php

namespace App\Http\Requests\Landing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValiderDemandeDemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'type_etablissement' => ['required', Rule::in(['primaire', 'secondaire', 'universitaire', 'centre_formation'])],
            // Meme logique que ValiderCommandeRequest : optionnel, cree un 2e
            // etablissement distinct plutot qu'un cumul de types.
            'type_etablissement_secondaire' => [
                'nullable',
                Rule::in(['primaire', 'secondaire', 'universitaire', 'centre_formation']),
                Rule::notIn([$this->input('type_etablissement')]),
            ],
        ];
    }
}
