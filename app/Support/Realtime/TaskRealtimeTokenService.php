<?php

namespace App\Support\Realtime;

use App\Models\User;
use Carbon\CarbonImmutable;

final class TaskRealtimeTokenService
{
    public function issue(User $user): string
    {
        $payload = [
            'sub' => $user->getKey(),
            'exp' => CarbonImmutable::now()
                ->addSeconds((int) config('tasks-realtime.auth.token_ttl_seconds'))
                ->timestamp,
        ];

        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->sign($encodedPayload);

        return $encodedPayload.'.'.$signature;
    }

    public function resolveUser(?string $token): ?User
    {
        if ($token === null || ! str_contains($token, '.')) {
            return null;
        }

        [$encodedPayload, $signature] = explode('.', $token, 2);

        if (! hash_equals($this->sign($encodedPayload), $signature)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);

        if (! is_array($payload) || ! isset($payload['sub'], $payload['exp'])) {
            return null;
        }

        if (CarbonImmutable::now()->timestamp >= (int) $payload['exp']) {
            return null;
        }

        return User::query()->find($payload['sub']);
    }

    private function sign(string $payload): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $payload, (string) config('app.key'), true));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
