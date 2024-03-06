<?php

namespace AccountsSecurity\database;

class Provider
{
    public static function isRegistered(string $username, string $columnName) : bool
    {
        $query = DatabaseManager::getDatabase()->prepare("SELECT $columnName FROM users WHERE username = LOWER(:username)");
        $query->bindValue(":username", $username);
        $result = $query->execute()->fetchArray(SQLITE3_ASSOC);
        return $result !== false and isset($result[$columnName]);
    }

    public static function getUserData(string $username, string $columnName) : ?string
    {
        $query = DatabaseManager::getDatabase()->prepare("SELECT $columnName FROM users WHERE username = LOWER(:username)");
        $query->bindValue(":username", $username);
        $result = $query->execute()->fetchArray(SQLITE3_ASSOC);
        return $result ? $result[$columnName] : null;
    }
}
