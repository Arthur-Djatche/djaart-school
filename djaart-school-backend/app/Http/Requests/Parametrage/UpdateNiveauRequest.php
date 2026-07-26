<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNiveauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('niveau'));
    }

    public function rules(): array
    {
        $niveau = $this->route('niveau');

        return [
            'filiere_id' => [
                'sometimes',
                'required',
                Rule::exists('filieres', 'id')->where('etablissement_id', $niveau->etablissement_id),
            ],
            'libelle' => ['sometimes', 'required', 'string', 'max:255'],
            'ordre' => ['sometimes', 'integer', 'min:1'],
            'type_systeme' => ['sometimes', 'required', Rule::in(['classique', 'lmd'])],
        ];
    }
}
