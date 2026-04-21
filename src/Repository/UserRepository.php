<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\Database;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;

final class UserRepository
{
    private Collection $collection;

    public function __construct()
    {
        $client = Database::client();
        $database = $client->selectDatabase(Database::dbName());
        $this->collection = $database->selectCollection('users');
        $this->collection->createIndex(['email' => 1], ['unique' => true]);
    }

    public function findById(string $id): ?array
    {
        $document = $this->collection->findOne(['_id' => new ObjectId($id)]);
        if ($document === null) {
            return null;
        }

        return $this->normalize($document);
    }

    public function findByEmail(string $email): ?array
    {
        $document = $this->collection->findOne(['email' => strtolower($email)]);
        if ($document === null) {
            return null;
        }

        return $this->normalize($document);
    }

    public function findCredentialByEmail(string $email): ?array
    {
        $document = $this->collection->findOne(['email' => strtolower($email)]);
        if ($document === null) {
            return null;
        }

        $normalized = $this->normalize($document);
        $normalized['passwordHash'] = (string)(((array)$document)['passwordHash'] ?? '');

        return $normalized;
    }

    public function create(string $name, string $email, string $passwordHash): array
    {
        $now = gmdate('c');
        $document = [
            'name' => $name,
            'email' => strtolower($email),
            'passwordHash' => $passwordHash,
            'createdAt' => $now,
            'updatedAt' => $now,
        ];

        $result = $this->collection->insertOne($document);
        return $this->findById((string)$result->getInsertedId());
    }

    private function normalize(object|array $document): array
    {
        $doc = (array)$document;

        return [
            'id' => (string)$doc['_id'],
            'name' => (string)($doc['name'] ?? ''),
            'email' => (string)($doc['email'] ?? ''),
            'createdAt' => (string)($doc['createdAt'] ?? ''),
            'updatedAt' => (string)($doc['updatedAt'] ?? ''),
        ];
    }
}
