<?php

declare(strict_types=1);

namespace App\Utils;

use MongoDB\BSON\ObjectId;

final class ObjectIdHelper
{
    public static function isValid(string $id): bool
    {
        try {
            new ObjectId($id);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
