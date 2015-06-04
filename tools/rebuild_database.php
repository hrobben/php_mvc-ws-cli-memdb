<?php

/** @noinspection PhpIncludeInspection */
require 'config/config.php';
/** @noinspection PhpIncludeInspection */
require 'classes/Database.php';

$dbFilename = DB_FILE;

if (file_exists($dbFilename)) {
    unlink($dbFilename);
}

$db = Database::getDb();

$db->exec('CREATE TABLE articles (id INTEGER, title STRING, body STRING)');
$db->exec('INSERT INTO articles (id, title, body) VALUES (1, "This is article #1", "Lorem ipsum etc.")');
$db->exec('INSERT INTO articles (id, title, body) VALUES (2, "This is article #2", "Article body test #2")');
$db->exec('INSERT INTO articles (id, title, body) VALUES (3, "This is article #3", "Article body test #3")');
$db->exec('INSERT INTO articles (id, title, body) VALUES (4, "This is article #4", "Article body test #4")');
$db->exec('INSERT INTO articles (id, title, body) VALUES (5, "This is article #5", "Article body test #5")');
$db->exec('INSERT INTO articles (id, title, body) VALUES (6, "This is article #6", "Article body test #6")');
$db->exec('INSERT INTO articles (id, title, body) VALUES (7, "This is article #7", "Article body test #7")');
$db->exec('INSERT INTO articles (id, title, body) VALUES (8, "This is article #8", "Article body test #8")');
