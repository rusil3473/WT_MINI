<?php

declare(strict_types=1);

use App\Config\Database;
use App\Controllers\AuthController;
use App\Controllers\TodoController;
use App\Repository\TodoRepository;
use App\Repository\UserRepository;
use App\Services\TokenService;
use App\Utils\Request;
use App\Utils\Response;

require __DIR__ . '/../vendor/autoload.php';

Database::init(dirname(__DIR__));

$todoController = new TodoController(new TodoRepository());
$authController = new AuthController(new UserRepository(), new TokenService());

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($method === 'OPTIONS') {
    Response::json(['message' => 'OK']);
    exit;
}

$payload = Request::jsonBody();

try {
    if ($path === '/api' && $method === 'GET') {
        Response::json([
            'name' => 'User Todo REST API',
            'version' => '2.0.0',
            'auth' => [
                'POST /api/auth/register',
                'POST /api/auth/login',
                'GET /api/auth/me',
            ],
            'todos' => [
                'GET /api/todos',
                'GET /api/todos/{id}',
                'POST /api/todos',
                'PUT /api/todos/{id}',
                'PATCH /api/todos/{id}',
                'DELETE /api/todos/{id}',
            ],
        ]);
        exit;
    }

    if ($path === '/api/auth/register' && $method === 'POST') {
        $authController->register($payload);
        exit;
    }

    if ($path === '/api/auth/login' && $method === 'POST') {
        $authController->login($payload);
        exit;
    }

    if ($path === '/api/auth/me' && $method === 'GET') {
        $user = $authController->resolveAuthenticatedUser();
        if ($user === null) {
            $authController->unauthorized();
            exit;
        }

        $authController->me($user);
        exit;
    }

    if ($path === '/api/todos' && $method === 'GET') {
        $user = $authController->resolveAuthenticatedUser();
        if ($user === null) {
            $authController->unauthorized();
            exit;
        }

        $todoController->index($user['id']);
        exit;
    }

    if ($path === '/api/todos' && $method === 'POST') {
        $user = $authController->resolveAuthenticatedUser();
        if ($user === null) {
            $authController->unauthorized();
            exit;
        }

        $todoController->store($user['id'], $payload);
        exit;
    }

    if (preg_match('#^/api/todos/([a-fA-F0-9]{24})$#', $path, $matches) === 1) {
        $user = $authController->resolveAuthenticatedUser();
        if ($user === null) {
            $authController->unauthorized();
            exit;
        }

        $id = $matches[1];

        if ($method === 'GET') {
            $todoController->show($id, $user['id']);
            exit;
        }

        if ($method === 'PUT' || $method === 'PATCH') {
            $todoController->update($id, $user['id'], $payload);
            exit;
        }

        if ($method === 'DELETE') {
            $todoController->destroy($id, $user['id']);
            exit;
        }
    }

    Response::json(['error' => 'Route not found.'], 404);
} catch (Throwable $exception) {
    $todoController->handleServerError($exception);
}
