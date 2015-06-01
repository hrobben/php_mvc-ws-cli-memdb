<?php

class Database
{
    private static $db = null;

    public static function getDb()
    {
        if (self::$db === null)
        {
            self::openDb();
        }

        return self::$db;
    }

    private static function openDb()
    {
        $db = new SQLite3(DB_FILE);

        self::$db = $db;
    }
}
