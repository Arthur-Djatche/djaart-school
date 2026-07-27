<?php

namespace App\Http\Requests\Pedagogie;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConduiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $classe = $this->route('classe');

        return [
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.inscription_id' => [
                'required',
                Rule::exists('inscriptions', 'id')
                    ->where('classe_id', $classe->id)
                    ->where('statut', '!=', 'annulee'),
            ],
            'lignes.*.absences' => ['sometimes', 'integer', 'min:0'],
            'lignes.*.absences_non_justifiees' => ['sometimes', 'integer', 'min:0'],
            'lignes.*.retards' => ['sometimes', 'integer', 'min:0'],
            'lignes.*.mention_travail' => ['nullable', Rule::in(['tableau_honneur', 'encouragements', 'avertissement', 'blame'])],
            'lignes.*.mention_conduite' => ['nullable', Rule::in(['tableau_honneur', 'encouragements', 'avertissement', 'blame'])],
        ];
    }
}
