<?php

namespace App\Http\Requests\Parametrage;

use App\Models\Filiere;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFiliereRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Filiere::class);
    }

    public function rules(): array
    {
        $etablissementId = $this->user()->hasRole('super_admin')
            ? $this->input('etablissement_id')
            : $this->user()->etablissement_id;

        return [
            'nom' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('filieres', 'code')->where('etablissement_id', $etablissementId),
            ],
            'chef_departement_id' => [
                'nullable',
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
