<?php

interface Database
{
    public static function connect();

    public function error();

    public function errno();

    public function escape($string);

    public function query($query);

    public function fetchArray($result);

    public function fetchRow($result);

    public function fetchAssoc($result);

    public function fetchObject($result);

    public function numRows($result);

    public function close();
}