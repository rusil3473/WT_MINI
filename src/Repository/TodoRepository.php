<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\Database;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\Operation\FindOneAndUpdate;

final class TodoRepository
{
    private Collection $collection;

    public function __construct()
    {
        $client = Database::client();
        $database = $client->selectDatabase(Database::dbName());
        $this->collection = $database->selectCollection(Database::collectionName());
        $this->collection->createIndex(['userId' => 1, 'createdAt' => -1]);
    }

    public function all(string $userId): array
    {
        $cursor = $this->collection->find(
            ['userId' => new ObjectId($userId)],
            ['sort' => ['createdAt' => -1]]
        );

        $todos = [];
        foreach ($cursor as $todo) {
            $todos[] = $this->normalize($todo);
        }

        return $todos;
    }

    public function findById(string $id, string $userId): ?array
    {
        $document = $this->collection->findOne([
            '_id' => new ObjectId($id),
            'userId' => new ObjectId($userId),
        ]);

        if ($document === null) {
            return null;
        }

        return $this->normalize($document);
    }

    public function create(string $userId, array $input): array
    {
        $now = gmdate('c');
        $document = [
            'userId' => new ObjectId($userId),
            'title' => $input['title'],
            'description' => $input['description'] ?? '',
            'completed' => (bool)($input['completed'] ?? false),
            'priority' => $input['priority'] ?? 'medium',
            'dueDate' => $input['dueDate'] ?? null,
            'createdAt' => $now,
            'updatedAt' => $now,
        ];

        $result = $this->collection->insertOne($document);
        return $this->findById((string)$result->getInsertedId(), $userId);
    }

    public function update(string $id, string $userId, array $input): ?array
    {
        $set = ['updatedAt' => gmdate('c')];

        if (array_key_exists('title', $input)) {
            $set['title'] = $input['title'];
        }

        if (array_key_exists('description', $input)) {
            $set['description'] = $input['description'];
        }

        if (array_key_exists('completed', $input)) {
            $set['completed'] = (bool)$input['completed'];
        }

        if (array_key_exists('priority', $input)) {
            $set['priority'] = $input['priority'];
        }

        if (array_key_exists('dueDate', $input)) {
            $set['dueDate'] = $input['dueDate'];
        }

        $updated = $this->collection->findOneAndUpdate(
            ['_id' => new ObjectId($id), 'userId' => new ObjectId($userId)],
            ['$set' => $set],
            ['returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER]
        );

        if ($updated === null) {
            return null;
        }

        return $this->normalize($updated);
    }

    public function delete(string $id, string $userId): bool
    {
        $result = $this->collection->deleteOne([
            '_id' => new ObjectId($id),
            'userId' => new ObjectId($userId),
        ]);

        return $result->getDeletedCount() > 0;
    }

    private function normalize(object|array $document): array
    {
        $doc = (array)$document;

        return [
            'id' => (string)$doc['_id'],
            'title' => (string)($doc['title'] ?? ''),
            'description' => (string)($doc['description'] ?? ''),
            'completed' => (bool)($doc['completed'] ?? false),
            'priority' => (string)($doc['priority'] ?? 'medium'),
            'dueDate' => $doc['dueDate'] ?? null,
            'createdAt' => (string)($doc['createdAt'] ?? ''),
            'updatedAt' => (string)($doc['updatedAt'] ?? ''),
        ];
    }
}
