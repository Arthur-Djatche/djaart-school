<?php

namespace App\Http\Requests\Parametrage;

use App\Models\Classe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Classe::class);
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
            'libelle' => ['required', 'string', 'max:255'],
            'effectif_max' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'etablissement_id' => $this->user()->hasRole('super_admin')
                ? ['required', 'exists:etablissements,id']
                : ['prohibited'],
        ];
    }
}
