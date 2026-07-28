<?php

namespace App\Http\Requests\Pedagogie;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('soumettreNotes', $this->route('affectation'));
    }

    public function rules(): array
    {
        $affectation = $this->route('affectation');

        return [
            'sequence_id' => [
                'required_without:semestre_id',
                'prohibited_if:semestre_id,present',
                Rule::exists('sequences', 'id')
                    ->where('niveau_id', $affectation->classe->niveau_id)
                    ->where('annee_academique_id', $affectation->annee_academique_id),
            ],
            'semestre_id' => [
                'required_without:sequence_id',
                'prohibited_if:sequence_id,present',
                Rule::exists('semestres', 'id')
                    ->where('niveau_id', $affectation->classe->niveau_id)
                    ->where('annee_academique_id', $affectation->annee_academique_id),
            ],
            'type_evaluation' => ['required', Rule::in(['sequence', 'cc', 'session_normale', 'rattrapage'])],
            'notes' => ['required', 'array', 'min:1'],
            'notes.*.apprenant_id' => [
                'required',
                Rule::exists('inscriptions', 'apprenant_id')
                    ->where('classe_id', $affectation->classe_id)
                    ->where(fn ($query) => $query->where('statut', '!=', 'annulee')),
            ],
            'notes.*.absent' => ['sometimes', 'boolean'],
            'notes.*.valeur' => ['nullable', 'numeric', 'min:0', 'max:20'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $typeEvaluation = $this->input('type_evaluation');
            $aSequence = $this->filled('sequence_id');
            $aSemestre = $this->filled('semestre_id');

            if ($aSequence && $typeEvaluation !== 'sequence') {
                $validator->errors()->add('type_evaluation', "Une séquence attend le type d'évaluation \"sequence\".");
            }

            if ($aSemestre && ! in_array($typeEvaluation, ['cc', 'session_normale', 'rattrapage'], true)) {
                $validator->errors()->add('type_evaluation', "Un semestre attend le type d'évaluation \"cc\", \"session_normale\" ou \"rattrapage\".");
            }
        });
    }
}
