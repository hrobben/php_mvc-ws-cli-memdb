<?php
if (PHP_SAPI === 'cli') {
    $req_uri = '';
    $arg2 = '/slug';
    if (!empty($argv[1])) {
        if (array_key_exists('2', $argv)) {
            $arg2 = ($argv[2] === '--xml' || $argv[2] === '--json' ? '/' . $argv[2] : '/slug');
        }
        switch ($argv[1]) {
            case ('--articles'):
                $req_uri = '/cli/' . str_replace('--', '', $argv[1] . $arg2);
                break;
            case (substr($argv[1], 0, 10) === '--article='):
                $u = '/cli/' . str_replace('--', '', $argv[1] . $arg2);
                $req_uri = str_replace('=', '/', $u);
                break;
            case ('--xml'):
                $req_uri = '/cli/xml';
                break;
            case ('--json'):
                $req_uri = '/cli/json';
                break;
            case ('--websocket'):
                $req_uri = '/websocket/';
                break;
            default:
                $req_uri = '/cli/help';
        }
    }
    define('REQ_URI', $req_uri);
}

