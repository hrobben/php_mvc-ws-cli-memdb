<?php
/**
 * @param $class
 *
 * All Controler(s) last 10 chars, Modul(s) last 5
 * First Char allways Uppercase....
 *
 * Switch is faster then if and elseif because switch asking 1 cycle and if every if or elseif.
 *
 */
function load($class)
{
    switch ($class) {
        case (substr($class, -10) === 'Controller'):
            $extraPath = 'controllers/';
            break;
        case (substr($class, -5) === 'Model'):
            $extraPath = 'models/';
            break;
        default:
            $extraPath = 'classes/';
    }

    $file = dirname(__FILE__) . '/' . $extraPath . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}

spl_autoload_register('load');
