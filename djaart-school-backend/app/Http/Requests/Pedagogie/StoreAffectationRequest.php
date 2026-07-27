<?php

namespace App\Http\Requests\Pedagogie;

use App\Models\AffectationEnseignant;
use App\Models\Classe;
use App\Models\Matiere;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAffectationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AffectationEnseignant::class);
    }

    public function rules(): array
    {
        $etablissementId = $this->user()->hasRole('super_admin')
            ? $this->input('etablissement_id')
            : $this->user()->etablissement_id;

        return [
            'classe_id' => [
                'required',
                Rule::exists('classes', 'id')->where('etablissement_id', $etablissementId),
            ],
            'matiere_id' => [
                'required',
                Rule::exists('matieres', 'id')->where('etablissement_id', $etablissementId),
            ],
            'enseignant_id' => [
                'required',
                Rule::exists('users', 'id')->where('etablissement_id', $etablissementId),
            ],
            'etablissement_id' => $this->user()->hasRole('super_admin')
                ? ['required', 'exists:etablissements,id']
                : ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $classe = Classe::find($this->input('classe_id'));
            $matiere = Matiere::find($this->input('matiere_id'));

            if ($classe && $matiere && $classe->niveau_id !== $matiere->niveau_id) {
                $validator->errors()->add(
                    'matiere_id',
                    "Cette matière n'appartient pas au niveau de la classe sélectionnée.",
                );
            }

            if ($classe && $matiere && AffectationEnseignant::where('classe_id', $classe->id)
                ->where('matiere_id', $matiere->id)
                ->where('annee_academique_id', $classe->annee_academique_id)
                ->exists()) {
                $validator->errors()->add(
                    'matiere_id',
                    'Cette matière est déjà affectée à un enseignant pour cette classe et cette année.',
                );
            }

            $enseignant = User::find($this->input('enseignant_id'));
            if ($enseignant && ! $enseignant->hasRole('enseignant')) {
                $validator->errors()->add(
                    'enseignant_id',
                    "L'utilisateur sélectionné n'a pas le rôle enseignant.",
                );
            }
        });
    }
}
