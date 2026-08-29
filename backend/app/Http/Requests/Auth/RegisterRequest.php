<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'birth_date' => ['required', 'date', 'before:today'],
            'role' => ['required', Rule::in([UserRole::Customer->value, UserRole::Executor->value])],
            'personal_data_consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Укажите ФИО.',
            'email.required' => 'Укажите email.',
            'email.email' => 'Некорректный email.',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован.',
            'password.required' => 'Укажите пароль.',
            'password.confirmed' => 'Пароли не совпадают.',
            'birth_date.required' => 'Укажите дату рождения.',
            'birth_date.before' => 'Дата рождения должна быть раньше сегодняшнего дня.',
            'role.required' => 'Выберите роль.',
            'personal_data_consent.accepted' => 'Необходимо согласие на обработку персональных данных.',
        ];
    }
}
