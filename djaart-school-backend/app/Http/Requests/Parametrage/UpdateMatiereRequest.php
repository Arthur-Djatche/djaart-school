<?php

namespace App\Http\Requests\Parametrage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMatiereRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('matiere'));
    }

    public function rules(): array
    {
        $matiere = $this->route('matiere');

        return [
            'niveau_id' => [
                'sometimes',
                'required',
                Rule::exists('niveaux', 'id')->where('etablissement_id', $matiere->etablissement_id),
            ],
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'groupe' => ['nullable', 'string', 'max:100'],
            'coefficient' => ['sometimes', 'numeric', 'min:0.5', 'max:20'],
            'credits_ects' => ['nullable', 'integer', 'min:1', 'max:60'],
            'ponderation_cc' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'ponderation_session_normale' => ['sometimes', 'integer', 'min:0', 'max:100'],
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
