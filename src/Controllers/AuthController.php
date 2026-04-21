<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repository\UserRepository;
use App\Services\TokenService;
use App\Utils\ObjectIdHelper;
use App\Utils\Request;
use App\Utils\Response;

final class AuthController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TokenService $tokenService
    ) {
    }

    public function register(array $payload): void
    {
        $name = trim((string)($payload['name'] ?? ''));
        $email = strtolower(trim((string)($payload['email'] ?? '')));
        $password = (string)($payload['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            Response::json(['error' => 'Name, email and password are required.'], 422);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['error' => 'Invalid email format.'], 422);
            return;
        }

        if (strlen($password) < 6) {
            Response::json(['error' => 'Password must be at least 6 characters.'], 422);
            return;
        }

        if ($this->userRepository->findByEmail($email) !== null) {
            Response::json(['error' => 'Email already registered.'], 409);
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $user = $this->userRepository->create($name, $email, $passwordHash);
        $token = $this->tokenService->issue($user);

        Response::json(['data' => ['user' => $user, 'token' => $token]], 201);
    }

    public function login(array $payload): void
    {
        $email = strtolower(trim((string)($payload['email'] ?? '')));
        $password = (string)($payload['password'] ?? '');

        if ($email === '' || $password === '') {
            Response::json(['error' => 'Email and password are required.'], 422);
            return;
        }

        $credential = $this->userRepository->findCredentialByEmail($email);
        if ($credential === null || !password_verify($password, $credential['passwordHash'])) {
            Response::json(['error' => 'Invalid credentials.'], 401);
            return;
        }

        unset($credential['passwordHash']);
        $token = $this->tokenService->issue($credential);

        Response::json(['data' => ['user' => $credential, 'token' => $token]]);
    }

    public function me(array $user): void
    {
        Response::json(['data' => $user]);
    }

    public function resolveAuthenticatedUser(): ?array
    {
        $token = Request::bearerToken();
        if ($token === null) {
            return null;
        }

        $payload = $this->tokenService->verify($token);
        if ($payload === null || !isset($payload['sub']) || !ObjectIdHelper::isValid((string)$payload['sub'])) {
            return null;
        }

        return $this->userRepository->findById((string)$payload['sub']);
    }

    public function unauthorized(): void
    {
        Response::json(['error' => 'Unauthorized. Please login.'], 401);
    }
}
