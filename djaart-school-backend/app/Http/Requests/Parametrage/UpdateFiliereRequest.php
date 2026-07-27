<?php

namespace App\Http\Requests\Parametrage;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'chef_departement_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('etablissement_id', $filiere->etablissement_id),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $chefDepartementId = $this->input('chef_departement_id');

            if (! $chefDepartementId) {
                return;
            }

            $chefDepartement = User::find($chefDepartementId);

            if ($chefDepartement && ! $chefDepartement->hasRole('enseignant')) {
                $validator->errors()->add(
                    'chef_departement_id',
                    "L'utilisateur sélectionné n'a pas le rôle enseignant.",
                );
            }
        });
    }
}
