<?php

try {
    print $dispatcher->dispatch();
} catch (Http404 $e) {
    if (PHP_SAPI === 'cli') {
        echo "\n\n\nPath not found (404)\n\n\n";
    } else {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true);
        echo '404';
    }
}
