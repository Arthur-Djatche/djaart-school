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
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in($assignableRoles)],
            'etablissement_id' => $this->user()->hasRole('super_admin')
                ? [Rule::requiredIf(fn () => $this->input('role') !== 'super_admin'), 'nullable', 'exists:etablissements,id']
                : ['prohibited'],
        ];
    }
}
