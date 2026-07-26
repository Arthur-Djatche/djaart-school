<?php

namespace App\Http\Requests\Parametrage;

use App\Models\Filiere;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'etablissement_id' => $this->user()->hasRole('super_admin')
                ? ['required', 'exists:etablissements,id']
                : ['prohibited'],
        ];
    }
}
