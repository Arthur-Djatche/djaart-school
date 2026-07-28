<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUniteEnseignementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('uniteEnseignement'));
    }

    public function rules(): array
    {
        $uniteEnseignement = $this->route('uniteEnseignement');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('unites_enseignement', 'code')->where('semestre_id', $uniteEnseignement->semestre_id)->ignore($uniteEnseignement->id),
            ],
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', Rule::in(['fondamentale', 'professionnelle', 'transversale'])],
        ];
    }
}
