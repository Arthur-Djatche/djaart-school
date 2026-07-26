<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('classe'));
    }

    public function rules(): array
    {
        $classe = $this->route('classe');

        return [
            'niveau_id' => [
                'sometimes',
                'required',
                Rule::exists('niveaux', 'id')->where('etablissement_id', $classe->etablissement_id),
            ],
            'annee_academique_id' => [
                'sometimes',
                'required',
                Rule::exists('annees_academiques', 'id')->where('etablissement_id', $classe->etablissement_id),
            ],
            'libelle' => ['sometimes', 'required', 'string', 'max:255'],
            'effectif_max' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ];
    }
}
