<?php

namespace App\Http\Requests\Parametrage;

use App\Models\Etablissement;
use App\Models\Filiere;
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
            'departement_id' => [
                'nullable',
                Rule::exists('departements', 'id')->where('etablissement_id', $etablissementId),
            ],
            'etablissement_id' => $this->user()->hasRole('super_admin')
                ? ['required', 'exists:etablissements,id']
                : ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $etablissementId = $this->user()->hasRole('super_admin')
                ? $this->input('etablissement_id')
                : $this->user()->etablissement_id;

            $etablissement = Etablissement::find($etablissementId);

            if (! $etablissement) {
                return;
            }

            $estUniversitaire = $etablissement->type_etablissement === 'universitaire';
            $departementId = $this->input('departement_id');

            if ($estUniversitaire && ! $departementId) {
                $validator->errors()->add('departement_id', 'Le département est obligatoire pour une filière universitaire.');
            }

            if (! $estUniversitaire && $departementId) {
                $validator->errors()->add('departement_id', "Le département ne s'applique qu'aux établissements universitaires.");
            }
        });
    }
}
