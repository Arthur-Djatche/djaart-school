<?php

namespace App\Http\Requests\Parametrage;

use App\Models\Matiere;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMatiereRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Matiere::class);
    }

    public function rules(): array
    {
        $etablissementId = $this->user()->hasRole('super_admin')
            ? $this->input('etablissement_id')
            : $this->user()->etablissement_id;

        return [
            'niveau_id' => [
                'required',
                Rule::exists('niveaux', 'id')->where('etablissement_id', $etablissementId),
            ],
            'nom' => ['required', 'string', 'max:255'],
            'coefficient' => ['sometimes', 'numeric', 'min:0.5', 'max:20'],
            'credits_ects' => ['nullable', 'integer', 'min:1', 'max:60'],
            'etablissement_id' => $this->user()->hasRole('super_admin')
                ? ['required', 'exists:etablissements,id']
                : ['prohibited'],
        ];
    }
}
