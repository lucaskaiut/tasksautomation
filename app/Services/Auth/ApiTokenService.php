<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class ApiTokenService
{
    /**
     * @param array{email:string,password:string} $credentials
     * @param array<int,string> $abilities
     */
    public function createToken(array $credentials, string $tokenName, array $abilities = ['*']): string
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        return $user->createToken($tokenName, $abilities)->plainTextToken;
    }
}

