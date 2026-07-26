<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMatiereRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('matiere'));
    }

    public function rules(): array
    {
        $matiere = $this->route('matiere');

        return [
            'niveau_id' => [
                'sometimes',
                'required',
                Rule::exists('niveaux', 'id')->where('etablissement_id', $matiere->etablissement_id),
            ],
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'coefficient' => ['sometimes', 'numeric', 'min:0.5', 'max:20'],
            'credits_ects' => ['nullable', 'integer', 'min:1', 'max:60'],
        ];
    }
}
