<?php

namespace App\Http\Requests\Pedagogie;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSemestreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('semestre'));
    }

    public function rules(): array
    {
        $semestre = $this->route('semestre');

        return [
            'numero' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('semestres', 'numero')
                    ->where(fn ($query) => $query
                        ->where('niveau_id', $semestre->niveau_id)
                        ->where('annee_academique_id', $semestre->annee_academique_id))
                    ->ignore($semestre->id),
            ],
            'libelle' => ['required', 'string', 'max:255'],
        ];
    }
}
