<?php

namespace App\Http\Requests\Landing;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:255'],
            'nombre_apprenants' => ['required', 'integer', 'min:1'],
            'telephone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'nom_etablissement' => ['required', 'string', 'max:255'],
        ];
    }
}
