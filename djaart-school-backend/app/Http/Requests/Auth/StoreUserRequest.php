<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        $assignableRoles = $this->user()->hasRole('super_admin')
            ? Role::pluck('name')
            : Role::where('name', '!=', 'super_admin')->pluck('name');

        return [
            'name' => ['required', 'string', 'max:255'],
            // Pas de contrainte unique : si l'e-mail correspond deja a un
            // compte existant (dans un autre etablissement), UserController::store
            // le rattache a celui-ci via le pivot plutot que de bloquer ou
            // dupliquer le compte.
            'email' => ['required', 'email'],
            // Le mot de passe n'est jamais choisi par l'admin qui cree le
            // compte : genere automatiquement et envoye par e-mail (cf.
            // UserController::store), meme mecanisme que la validation
            // d'une commande.
            'role' => ['required', Rule::in($assignableRoles)],
            'etablissement_id' => $this->user()->hasRole('super_admin')
                ? [Rule::requiredIf(fn () => $this->input('role') !== 'super_admin'), 'nullable', 'exists:etablissements,id']
                : ['prohibited'],
        ];
    }
}
