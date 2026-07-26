<?php

namespace App\Http\Requests\Parametrage;

use App\Models\Niveau;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNiveauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Niveau::class);
    }

    public function rules(): array
    {
        $etablissementId = $this->user()->hasRole('super_admin')
            ? $this->input('etablissement_id')
            : $this->user()->etablissement_id;

        return [
            'filiere_id' => [
                'required',
                Rule::exists('filieres', 'id')->where('etablissement_id', $etablissementId),
            ],
            'libelle' => ['required', 'string', 'max:255'],
            'ordre' => ['sometimes', 'integer', 'min:1'],
            'type_systeme' => ['required', Rule::in(['classique', 'lmd'])],
            'etablissement_id' => $this->user()->hasRole('super_admin')
                ? ['required', 'exists:etablissements,id']
                : ['prohibited'],
        ];
    }
}
