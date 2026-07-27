<?php

namespace App\Http\Requests\Parametrage;

use App\Models\Matiere;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'ponderation_cc' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'ponderation_session_normale' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'etablissement_id' => $this->user()->hasRole('super_admin')
                ? ['required', 'exists:etablissements,id']
                : ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $cc = $this->input('ponderation_cc');
            $sn = $this->input('ponderation_session_normale');

            if ($cc !== null && $sn !== null && (int) $cc + (int) $sn !== 100) {
                $validator->errors()->add(
                    'ponderation_session_normale',
                    'La pondération CC + Session Normale doit être égale à 100.',
                );
            }
        });
    }
}
