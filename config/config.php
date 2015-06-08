<?php
define('WS_PORT', '9060');  // websocket port
define('DB_FILE', ':memory:');
// define('DB_FILE','sqlite3.db');
if (DB_FILE === ':memory:') {    // recreate dbase if in memory
    $dbFilename = DB_FILE;

    if (file_exists($dbFilename)) {
        unlink($dbFilename);
    }

    $db = SQLiteDbase::connect();

    $db->exec('CREATE TABLE articles (id INTEGER, title STRING, body STRING)');
    $db->exec('INSERT INTO articles (id, title, body) VALUES (1, "This is article #1", "Lorem ipsum etc.")');
    $db->exec('INSERT INTO articles (id, title, body) VALUES (2, "This is article #2", "Article body test #2")');
    $db->exec('INSERT INTO articles (id, title, body) VALUES (3, "This is article #3", "Article body test #3")');
    $db->exec('INSERT INTO articles (id, title, body) VALUES (4, "This is article #4", "Article body test #4")');
    $db->exec('INSERT INTO articles (id, title, body) VALUES (5, "This is article #5", "Article body test #5")');
    $db->exec('INSERT INTO articles (id, title, body) VALUES (6, "This is article #6", "Article body test #6")');
    $db->exec('INSERT INTO articles (id, title, body) VALUES (7, "This is article #7", "Article body test #7")');
    $db->exec('INSERT INTO articles (id, title, body) VALUES (8, "This is article #8", "Article body test #8")');

}

define('MAGIC_GUID', '258EAFA5-E914-47DA-95CA-C5AB0DC85B11'); // websocket HASH key

if (PHP_SAPI !== 'cli') {
    $url = preg_replace('{/$}', '', "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
    define('URL', $url);
    define('USER_IP', $_SERVER['REMOTE_ADDR']);
    define('REQ_URI', $_SERVER['REQUEST_URI']);
} else {
    define('URL', 'http://localhost/cli');
}
