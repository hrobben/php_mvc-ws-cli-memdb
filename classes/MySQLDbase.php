<?php

class Mysql_Database implements Database
{
    private static $_link;

    public static function connect($server_credentials = [], $new_link = true, $client_flags = 0)
    {
        extract($server_credentials);
        // Validate required elements
        if ('' === $server || null === $server) {
            throw new Exception(sprintf('Invalid $value: %s', serialize($server_credentials))); // TODO: spl error
        }

        self::$_link = mysql_connect($server, $username, $password, $new_link, $client_flags);
    }

    public function error()
    {
        return mysql_errno($this->_link);
    }

    public function errno()
    {
        return mysql_error($this->_link);
    }

    public function escape($string)
    {
        return mysql_real_escape_string($string, $this->_link);
    }

    public function query($query)
    {
        return mysql_query($query, $this->_link);
    }

    public function fetchArray($result, $array_type = MYSQL_BOTH)
    {
        return mysql_fetch_array($result, $array_type);
    }

    public function fetchRow($result)
    {
        return mysql_fetch_row($result);
    }

    public function fetchAssoc($result)
    {
        return mysql_fetch_assoc($result);
    }

    public function fetchObject($result)
    {
        return mysql_fetch_object($result);
    }

    public function numRows($result)
    {
        return mysql_num_rows($result);
    }

    public function close()
    {
        return mysql_close($this->_link);
    }
}
