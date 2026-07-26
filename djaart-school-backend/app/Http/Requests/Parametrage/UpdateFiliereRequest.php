<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFiliereRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('filiere'));
    }

    public function rules(): array
    {
        $filiere = $this->route('filiere');

        return [
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('filieres', 'code')->where('etablissement_id', $filiere->etablissement_id)->ignore($filiere->id),
            ],
        ];
    }
}
