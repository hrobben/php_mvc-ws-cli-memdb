<?php

require_once 'autoloader.php';
error_reporting(E_ALL);
ini_set('display_errors', true);  // set false in production...

$dispatcher = new Dispatcher();

define('BASE_PATH', __DIR__);

require './config/config.php';

require('cli.php');

require('start.php');