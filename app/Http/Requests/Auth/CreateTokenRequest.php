<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'token_name' => ['sometimes', 'string', 'max:255'],
            'abilities' => ['sometimes', 'array'],
            'abilities.*' => ['string', 'max:100'],
        ];
    }

    /**
     * @return array{email:string,password:string}
     */
    public function credentials(): array
    {
        /** @var array{email:string,password:string} $credentials */
        $credentials = $this->only(['email', 'password']);

        return $credentials;
    }

    public function tokenName(): string
    {
        return (string) ($this->validated('token_name') ?? 'worker');
    }

    /**
     * @return array<int,string>
     */
    public function abilities(): array
    {
        /** @var array<int,string> $abilities */
        $abilities = $this->validated('abilities', ['*']);

        return $abilities;
    }
}
