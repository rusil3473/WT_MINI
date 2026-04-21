<?php

declare(strict_types=1);

namespace App\Utils;

final class Request
{
    public static function jsonBody(): array
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        return is_array($payload) ? $payload : [];
    }

    public static function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if ($header === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (!is_string($header) || $header === '') {
            return null;
        }

        if (preg_match('/^Bearer\s+(.*)$/i', $header, $matches) !== 1) {
            return null;
        }

        return trim($matches[1]);
    }
}
