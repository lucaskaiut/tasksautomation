<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CreateTokenRequest;
use App\Services\Auth\ApiTokenService;
use Illuminate\Http\JsonResponse;

class TokenController extends Controller
{
    public function store(CreateTokenRequest $request, ApiTokenService $service): JsonResponse
    {
        $token = $service->createToken(
            credentials: $request->credentials(),
            tokenName: $request->tokenName(),
            abilities: $request->abilities(),
        );

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            'message' => 'Token criado com sucesso.',
        ], 201);
    }
}
