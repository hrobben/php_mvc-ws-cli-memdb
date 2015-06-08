<?php

class SQLiteDbase implements Database
{
    private static $_db;

    public static function connect()
    {
        if (self::$_db === null) {
            self::openDb();
        }

        return self::$_db;
    }

    private static function openDb()
    {
        $db = new SQLite3(DB_FILE);

        self::$_db = $db;
    }

    public function error()
    {

    }

    public function errno()
    {

    }

    public function escape($string)
    {

    }

    public function query($query)
    {

    }

    public function fetchArray($result, $array_type = MYSQL_BOTH)
    {

    }

    public function fetchRow($result)
    {

    }

    public function fetchAssoc($result)
    {

    }

    public function fetchObject($result)
    {

    }

    public function numRows($result)
    {

    }

    public function close()
    {

    }

}