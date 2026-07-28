<?php

namespace App\Http\Requests\Landing;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemandeDemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'nom_etablissement' => ['required', 'string', 'max:255'],
            'effectif_estime' => ['nullable', 'integer', 'min:1'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
