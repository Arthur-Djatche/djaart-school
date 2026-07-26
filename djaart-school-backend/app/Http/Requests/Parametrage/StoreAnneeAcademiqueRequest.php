<?php

namespace App\Http\Requests\Parametrage;

use App\Models\AnneeAcademique;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnneeAcademiqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AnneeAcademique::class);
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:255'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'statut' => ['sometimes', Rule::in(['en_preparation', 'en_cours', 'cloturee'])],
            'etablissement_id' => $this->user()->hasRole('super_admin')
                ? ['required', 'exists:etablissements,id']
                : ['prohibited'],
        ];
    }
}
