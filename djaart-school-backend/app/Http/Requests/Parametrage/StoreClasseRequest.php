<?php

namespace App\Http\Requests\Parametrage;

use App\Models\Classe;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'professeur_principal_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('etablissement_id', $etablissementId),
            ],
            'libelle' => ['required', 'string', 'max:255'],
            'effectif_max' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'etablissement_id' => $this->user()->hasRole('super_admin')
                ? ['required', 'exists:etablissements,id']
                : ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $professeurPrincipalId = $this->input('professeur_principal_id');

            if (! $professeurPrincipalId) {
                return;
            }

            $professeurPrincipal = User::find($professeurPrincipalId);

            if ($professeurPrincipal && ! $professeurPrincipal->hasRole('enseignant')) {
                $validator->errors()->add(
                    'professeur_principal_id',
                    "L'utilisateur sélectionné n'a pas le rôle enseignant.",
                );
            }
        });
    }
}
