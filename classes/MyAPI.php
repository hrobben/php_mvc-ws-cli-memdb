<?php

/*
 * The example use of this class below.
 *
 */

class MyAPI extends API
{
    protected $User;

    public function __construct($request, $origin)
    {
        parent::__construct($request);

// Abstracted out for example
        $APIKey = new Models\APIKey();
        $User = new Models\User();

        if (!array_key_exists('apiKey', $this->request)) {
            throw new Exception('No API Key provided');
        } elseif (!$APIKey->verifyKey($this->request['apiKey'], $origin)) {
            throw new Exception('Invalid API Key');
        } elseif (array_key_exists('token', $this->request) &&
            !$User->get('token', $this->request['token'])
        ) {

            throw new Exception('Invalid User Token');
        }

        $this->User = $User;
    }

    /**
     * Example of an Endpoint
     */
    protected function example()
    {
        if ($this->method === 'GET') {
            return 'Your name is ' . $this->User->name;
        } else {
            return 'Only accepts GET requests ';
        }
    }
}
/*
// Example use of this Class.
// Requests from the same server don't have a HTTP_ORIGIN header
if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

try {
    $API = new MyAPI($_REQUEST['request'], $_SERVER['HTTP_ORIGIN']);
    echo $API->processAPI();
} catch (Exception $e) {
    echo json_encode(Array('error' => $e->getMessage()));
}*/