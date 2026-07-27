<?php

namespace App\Http\Requests\Pedagogie;

use App\Models\Semestre;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSemestreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Semestre::class);
    }

    public function rules(): array
    {
        $etablissementId = $this->user()->hasRole('super_admin')
            ? $this->input('etablissement_id')
            : $this->user()->etablissement_id;

        return [
            'niveau_id' => [
                'required',
                Rule::exists('niveaux', 'id')->where('etablissement_id', $etablissementId),
            ],
            'annee_academique_id' => [
                'required',
                Rule::exists('annees_academiques', 'id')->where('etablissement_id', $etablissementId),
            ],
            'numero' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('semestres', 'numero')->where(fn ($query) => $query
                    ->where('niveau_id', $this->input('niveau_id'))
                    ->where('annee_academique_id', $this->input('annee_academique_id'))),
            ],
            'libelle' => ['required', 'string', 'max:255'],
            'etablissement_id' => $this->user()->hasRole('super_admin')
                ? ['required', 'exists:etablissements,id']
                : ['prohibited'],
        ];
    }
}
