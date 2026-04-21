<?php

declare(strict_types=1);

namespace App\Services;

final class TokenService
{
    private string $secret;
    private int $ttl;

    public function __construct()
    {
        $this->secret = getenv('JWT_SECRET') ?: 'change-this-secret-key';
        $this->ttl = (int)(getenv('JWT_TTL_SECONDS') ?: 86400);
    }

    public function issue(array $user): string
    {
        $issuedAt = time();
        $payload = [
            'sub' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'iat' => $issuedAt,
            'exp' => $issuedAt + $this->ttl,
        ];

        return $this->encode($payload);
    }

    public function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header64, $payload64, $signature64] = $parts;
        $input = $header64 . '.' . $payload64;
        $expected = $this->base64UrlEncode(hash_hmac('sha256', $input, $this->secret, true));

        if (!hash_equals($expected, $signature64)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($payload64), true);
        if (!is_array($payload)) {
            return null;
        }

        if (!isset($payload['exp']) || time() >= (int)$payload['exp']) {
            return null;
        }

        return $payload;
    }

    private function encode(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $header64 = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payload64 = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', $header64 . '.' . $payload64, $this->secret, true);
        $signature64 = $this->base64UrlEncode($signature);

        return $header64 . '.' . $payload64 . '.' . $signature64;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}
