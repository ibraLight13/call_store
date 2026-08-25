<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return non-empty-array<non-empty-string, non-empty-list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[\pL\pN_.-]+$/u', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
            'account_type' => ['required', 'in:buyer,seller'],
        ];
    }

    /**
     * @return array{name: string, username: string, email: string, password: string, account_type: 'buyer'|'seller'}
     */
    public function accountData(): array
    {
        return [
            'name' => (string) $this->str('name'),
            'username' => (string) $this->str('username'),
            'email' => (string) $this->str('email'),
            'password' => (string) $this->str('password'),
            'account_type' => $this->input('account_type') === 'seller' ? 'seller' : 'buyer',
        ];
    }
}
