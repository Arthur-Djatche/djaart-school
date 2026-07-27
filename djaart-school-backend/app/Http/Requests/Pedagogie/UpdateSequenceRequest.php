<?php

namespace App\Http\Requests\Pedagogie;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('sequence'));
    }

    public function rules(): array
    {
        $sequence = $this->route('sequence');

        return [
            'numero' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('sequences', 'numero')
                    ->where(fn ($query) => $query
                        ->where('niveau_id', $sequence->niveau_id)
                        ->where('annee_academique_id', $sequence->annee_academique_id))
                    ->ignore($sequence->id),
            ],
            'libelle' => ['required', 'string', 'max:255'],
        ];
    }
}
