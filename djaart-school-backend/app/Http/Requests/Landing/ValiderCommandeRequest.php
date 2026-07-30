<?php

namespace App\Http\Requests\Landing;

use App\Support\GrantablePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValiderCommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'type_etablissement' => ['required', Rule::in(['primaire', 'secondaire', 'universitaire', 'centre_formation'])],
            'duree_mois' => ['required', 'integer', 'min:1', 'max:60'],
            'permissions' => ['present', 'array'],
            'permissions.*' => [Rule::in(GrantablePermissions::cles())],
        ];
    }
}
