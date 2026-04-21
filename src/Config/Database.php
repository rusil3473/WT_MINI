<?php

declare(strict_types=1);

namespace App\Config;

use Dotenv\Dotenv;
use MongoDB\Client;

final class Database
{
    private static ?Client $client = null;

    public static function init(string $basePath): void
    {
        if (file_exists($basePath . DIRECTORY_SEPARATOR . '.env')) {
            $dotenv = Dotenv::createImmutable($basePath);
            $dotenv->safeLoad();
        }
    }

    public static function client(): Client
    {
        if (self::$client === null) {
            $uri = getenv('MONGODB_URI') ?: 'mongodb://127.0.0.1:27017';
            self::$client = new Client($uri);
        }

        return self::$client;
    }

    public static function dbName(): string
    {
        return getenv('MONGODB_DB') ?: 'todo_app';
    }

    public static function collectionName(): string
    {
        return getenv('MONGODB_COLLECTION') ?: 'todos';
    }
}
