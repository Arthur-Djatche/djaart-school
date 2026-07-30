<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $targetUser = $this->route('user');

        $assignableRoles = $this->user()->hasRole('super_admin')
            ? Role::pluck('name')
            : Role::where('name', '!=', 'super_admin')->pluck('name');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($targetUser->id)],
            // Comme a la creation, un admin ne choisit jamais un mot de passe
            // pour un tiers : ce booleen declenche une reinitialisation
            // (nouveau mot de passe genere, envoye par e-mail).
            'reinitialiser_mot_de_passe' => ['sometimes', 'boolean'],
            'role' => [
                'sometimes',
                'required',
                Rule::in($assignableRoles),
                // Un acteur ne doit jamais pouvoir changer son propre role
                // (auto-elevation/auto-retrogradation), meme un super_admin.
                Rule::prohibitedIf($this->user()->is($targetUser)),
            ],
        ];
    }
}
