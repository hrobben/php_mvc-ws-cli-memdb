<?php

class Dispatcher
{
    protected $routes = array(
        '^/$' => array('index', 'index'),
        '^/articles/$' => array('articles', 'index'),
        '^/articles/xml$' => array('articles', 'index'),
        '^/articles/json$' => array('articles', 'index'),
        '^/articles/article/([^/]+)/(.+)$' => array('articles', 'view'),
    );

    public function dispatch()
    {
        $fullRequestUri = $GLOBALS['REQ_URI'];
        // only needed for old url with index.php?url=true  in the request...
/*        $requestUriParts = explode('?', $fullRequestUri, 2);
        $relativeRequestUri = substr($requestUriParts[0], strlen(BASE_URL));  // never used elsewhere....*/

        $routeParams = array(); // to put in extra params.
        $queryParams = $_GET;
        $extraData = $_POST;

        foreach ($this->routes as $route => $controllerInfo) {
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

                /*                $output = call_user_func_array(
                                    array($controller, 'action' . ucfirst($controllerAction)),
                                    $routeParams
                                );*/
                // more performance with solution below.
                // $this->routes gives max 2 params through to method.
                $act = 'action' . ucfirst($controllerAction);  // first action setting, then call method with or without max 2 params.
                $output = $controller->$act((isset($routeParams[0]) ? $routeParams[0] : ''), (isset($routeParams[1]) ? $routeParams[1] : ''));

                // print $output;

                return $output;
            }
        }

        throw new Http404();
    }
}
