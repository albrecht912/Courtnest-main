<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
       Validator::make($input, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'phone_number' => ['required', 'string', 'max:20'],
        'password' => [
        'required',
        'string',
        'min:12',
        'confirmed',
        'regex:/[a-z]/',
        'regex:/[A-Z]/',
        'regex:/[^A-Za-z0-9]/',
    ],
    'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
], [
    'password.min' => 'Password must be at least 12 characters.',
    'password.regex' => 'Password must contain uppercase letter, lowercase letter, and special character.',
])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'phone_number' => $input['phone_number'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
