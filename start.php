<?php

try {
    print $dispatcher->dispatch();
} catch (Http404 $e) {
    if (ISCLI) {
        echo "Path not found (404)";
    } else {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true);
        echo '404';
    }
}
