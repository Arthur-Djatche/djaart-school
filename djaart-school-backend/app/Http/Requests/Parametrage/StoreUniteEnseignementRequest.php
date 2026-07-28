<?php

namespace App\Http\Requests\Parametrage;

use App\Models\UniteEnseignement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUniteEnseignementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', UniteEnseignement::class);
    }

    public function rules(): array
    {
        $etablissementId = $this->user()->hasRole('super_admin')
            ? $this->input('etablissement_id')
            : $this->user()->etablissement_id;

        return [
            'semestre_id' => [
                'required',
                Rule::exists('semestres', 'id')->where('etablissement_id', $etablissementId),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('unites_enseignement', 'code')->where('semestre_id', $this->input('semestre_id')),
            ],
            'nom' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['fondamentale', 'professionnelle', 'transversale'])],
            'etablissement_id' => $this->user()->hasRole('super_admin')
                ? ['required', 'exists:etablissements,id']
                : ['prohibited'],
        ];
    }
}
