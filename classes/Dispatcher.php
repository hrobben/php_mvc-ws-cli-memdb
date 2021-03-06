<?php

class Dispatcher
{
    private static $routes = array(
        '^/$' => array('index', 'index'),
        '^/articles/$' => array('articles', 'index'),
        '^/websocket/$' => array('websocket', 'index'),
        '^/cli/$' => array('cli', 'index'),
        '^/cli/articles/(.+)$' => array('cli', 'index'),
        '^/cli/help$' => array('cli', 'help'),
        '^/articles/article/([^/]+)/(.+)$' => array('articles', 'view'),
        '^/cli/article/([^/]+)/(.+)$' => array('cli', 'view')
    );

    public function dispatch()
    {
        $fullRequestUri = REQ_URI;

        $routeParams = array();
        $queryParams = $_GET;
        $extraData = $_POST;

        foreach (self::$routes as $route => $controllerInfo) {
            if (preg_match('@' . $route . '@', $fullRequestUri, $routeParams) === 1) {  // int preg_match ( string $pattern , string $subject [, array &$matches [, int $flags = 0 [, int $offset = 0 ]]] )
                list($controllerName, $controllerAction) = $controllerInfo;

                // Remove full regex pattern
                array_shift($routeParams);
                $routeParams = array_map(     // this is faster then foreach.
                    'urldecode',
                    $routeParams
                );

                $controllerClass = ucfirst($controllerName) . 'Controller';
                $controller = new $controllerClass($queryParams, $extraData);

                $output = call_user_func_array(
                    array($controller, 'action' . ucfirst($controllerAction)),
                    $routeParams
                );

                return $output;
            }
        }

        throw new Http404();
    }
}
