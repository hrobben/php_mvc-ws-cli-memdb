<?php
if (PHP_SAPI === 'cli') {
    if (!empty($argv[1])) {
        switch ($argv[1]) {
            case ('--articles'):
                $req_uri = '/cli/' . str_replace('--', '', $argv[1] . (isset($argv[2]) ? ($argv[2] == '--xml' || $argv[2] == '--json' ? '/' . $argv[2] : '/slug') : '/slug'));
                break;
            case (substr($argv[1], 0, 10) == '--article='):
                $u = '/cli/' . str_replace('--', '', $argv[1] . (isset($argv[2]) ? ($argv[2] == '--xml' || $argv[2] == '--json' ? '/' . $argv[2] : '/slug') : '/slug'));
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
    $GLOBALS['REQ_URI'] = $req_uri;
    // define(REQ_URI, $req_uri);
}

