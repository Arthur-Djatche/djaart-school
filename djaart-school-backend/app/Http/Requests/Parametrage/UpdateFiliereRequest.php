<?php

namespace App\Http\Requests\Parametrage;

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
            'departement_id' => [
                'nullable',
                Rule::exists('departements', 'id')->where('etablissement_id', $filiere->etablissement_id),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $filiere = $this->route('filiere');
            $estUniversitaire = $filiere->etablissement->type_etablissement === 'universitaire';
            $departementId = $this->input('departement_id', $filiere->departement_id);

            if ($estUniversitaire && ! $departementId) {
                $validator->errors()->add('departement_id', 'Le département est obligatoire pour une filière universitaire.');
            }

            if (! $estUniversitaire && $departementId) {
                $validator->errors()->add('departement_id', "Le département ne s'applique qu'aux établissements universitaires.");
            }
        });
    }
}
