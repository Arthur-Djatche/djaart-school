<?php

namespace App\Http\Requests\Inscription;

use App\Models\Inscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Inscription::class);
    }

    public function rules(): array
    {
        $classeRule = $this->user()->hasRole('super_admin')
            ? Rule::exists('classes', 'id')
            : Rule::exists('classes', 'id')->where('etablissement_id', $this->user()->etablissement_id);

        return [
            'classe_id' => ['required', $classeRule],

            'apprenant_id' => [
                'required_without:apprenant',
                Rule::exists('apprenants', 'id')->where('etablissement_id', $this->user()->etablissement_id),
            ],

            'apprenant' => ['required_without:apprenant_id', 'array'],
            'apprenant.nom' => ['required_with:apprenant', 'string', 'max:255'],
            'apprenant.prenom' => ['required_with:apprenant', 'string', 'max:255'],
            'apprenant.date_naissance' => ['required_with:apprenant', 'date', 'before:today'],
            'apprenant.sexe' => ['required_with:apprenant', Rule::in(['M', 'F'])],
            'apprenant.telephone' => ['nullable', 'string', 'max:30'],
            'apprenant.email' => ['nullable', 'email'],
            'apprenant.adresse' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('apprenant_id') && $this->filled('apprenant')) {
                $validator->errors()->add(
                    'apprenant_id',
                    'Choisissez soit un apprenant existant, soit la création d\'un nouvel apprenant, pas les deux.'
                );
            }
        });
    }
}
