<?php

namespace App\Http\Requests\Parametrage;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'professeur_principal_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('etablissement_id', $classe->etablissement_id),
            ],
            'libelle' => ['sometimes', 'required', 'string', 'max:255'],
            'effectif_max' => ['sometimes', 'integer', 'min:1', 'max:500'],
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
