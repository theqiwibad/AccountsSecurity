<?php

namespace AccountsSecurity\database;

use SQLite3;

class DatabaseManager
{
    private static SQLite3 $database;

    public static function init(string $dataFolder) : void
    {
        self::$database = new SQLite3($dataFolder . "users.sqlite");
        self::$database->exec(
            "CREATE TABLE IF NOT EXISTS users
            (
                username TEXT PRIMARY KEY,
                password TEXT,
                lowerPassword TEXT,
                xboxUserId TEXT,
                address TEXT NOT NULL,
                clientRandomId TEXT NOT NULL,
                deviceModel TEXT NOT NULL,
                CONSTRAINT password_xor_xboxUserId CHECK (password IS NOT NULL AND xboxUserId IS NULL OR password IS NULL AND xboxUserId IS NOT NULL)
            )"
        );
    }

    public static function getDatabase() : SQLite3
    {
        return self::$database;
    }
}
