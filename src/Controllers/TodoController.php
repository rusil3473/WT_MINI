<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repository\TodoRepository;
use App\Utils\ObjectIdHelper;
use App\Utils\Response;
use Throwable;

final class TodoController
{
    public function __construct(private readonly TodoRepository $todoRepository)
    {
    }

    public function index(string $userId): void
    {
        Response::json(['data' => $this->todoRepository->all($userId)]);
    }

    public function show(string $id, string $userId): void
    {
        if (!ObjectIdHelper::isValid($id)) {
            Response::json(['error' => 'Invalid todo id.'], 400);
            return;
        }

        $todo = $this->todoRepository->findById($id, $userId);
        if ($todo === null) {
            Response::json(['error' => 'Todo not found.'], 404);
            return;
        }

        Response::json(['data' => $todo]);
    }

    public function store(string $userId, array $payload): void
    {
        $title = trim((string)($payload['title'] ?? ''));
        if ($title === '') {
            Response::json(['error' => 'Title is required.'], 422);
            return;
        }

        $priority = strtolower(trim((string)($payload['priority'] ?? 'medium')));
        if (!in_array($priority, ['low', 'medium', 'high'], true)) {
            Response::json(['error' => 'Priority must be low, medium, or high.'], 422);
            return;
        }

        $dueDate = $this->sanitizeDueDate($payload['dueDate'] ?? null);
        if (($payload['dueDate'] ?? null) !== null && $dueDate === null) {
            Response::json(['error' => 'dueDate must be YYYY-MM-DD or null.'], 422);
            return;
        }

        $todo = $this->todoRepository->create($userId, [
            'title' => $title,
            'description' => (string)($payload['description'] ?? ''),
            'completed' => (bool)($payload['completed'] ?? false),
            'priority' => $priority,
            'dueDate' => $dueDate,
        ]);

        Response::json(['data' => $todo], 201);
    }

    public function update(string $id, string $userId, array $payload): void
    {
        if (!ObjectIdHelper::isValid($id)) {
            Response::json(['error' => 'Invalid todo id.'], 400);
            return;
        }

        if (array_key_exists('title', $payload) && trim((string)$payload['title']) === '') {
            Response::json(['error' => 'Title cannot be empty.'], 422);
            return;
        }

        if (array_key_exists('priority', $payload)) {
            $priority = strtolower(trim((string)$payload['priority']));
            if (!in_array($priority, ['low', 'medium', 'high'], true)) {
                Response::json(['error' => 'Priority must be low, medium, or high.'], 422);
                return;
            }
            $payload['priority'] = $priority;
        }

        if (array_key_exists('dueDate', $payload)) {
            $dueDate = $this->sanitizeDueDate($payload['dueDate']);
            if ($payload['dueDate'] !== null && $dueDate === null) {
                Response::json(['error' => 'dueDate must be YYYY-MM-DD or null.'], 422);
                return;
            }
            $payload['dueDate'] = $dueDate;
        }

        $updated = $this->todoRepository->update($id, $userId, $payload);

        if ($updated === null) {
            Response::json(['error' => 'Todo not found.'], 404);
            return;
        }

        Response::json(['data' => $updated]);
    }

    public function destroy(string $id, string $userId): void
    {
        if (!ObjectIdHelper::isValid($id)) {
            Response::json(['error' => 'Invalid todo id.'], 400);
            return;
        }

        $deleted = $this->todoRepository->delete($id, $userId);
        if (!$deleted) {
            Response::json(['error' => 'Todo not found.'], 404);
            return;
        }

        Response::noContent();
    }

    public function handleServerError(Throwable $exception): void
    {
        Response::json([
            'error' => 'Internal server error.',
            'message' => $exception->getMessage(),
        ], 500);
    }

    private function sanitizeDueDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $text = (string)$value;
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $text);

        if ($date === false || $date->format('Y-m-d') !== $text) {
            return null;
        }

        return $text;
    }
}
