#!/usr/bin/env php
<?php

class echoServer extends WebSocketServer
{
    protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.

    protected function process($user, $message)
    {
        switch ($message) {
            case "hello" :
                $this->send($user, "hello human");
                break;
            case "help"   :
                $this->send($user, "help: <br> articles  => to show all articles.<br>XML => to make output shown in XML on/off.<br>JSON => output in JSON on/off.<br>article=1 => gives single article id=1");
                break;
            case (substr($message, 0, 8) == 'article='):
                $xj = ($GLOBALS['XML'] ? 'xml' : '') . ($GLOBALS['JSON'] ? 'json' : '');
                $u = '/articles/article/' . substr($message, -1 * (strlen($message) - 8)) . '/' . (empty($xj) ? 'slug' : $xj);
                $GLOBALS['REQ_URI'] = str_replace('=', '/', $u);
                $dispatcher = new Dispatcher();
                $view = $dispatcher->dispatch();
                $this->send($user, $view);
                break;
            case "articles"   :
                $GLOBALS['REQ_URI'] = '/articles/';
                $dispatcher = new Dispatcher();
                $view = $dispatcher->dispatch();
                $this->send($user, $view);
                break;
            case "XML"   :
                $GLOBALS['XML'] = !$GLOBALS['XML'];
                $GLOBALS['JSON'] = false;
                $this->send($user, 'XML = ' . ($GLOBALS['XML'] ? 'true' : 'false'));
                break;
            case "JSON"   :
                $GLOBALS['JSON'] = !$GLOBALS['JSON'];
                $GLOBALS['XML'] = false;
                $this->send($user, 'JSON = ' . ($GLOBALS['JSON'] ? 'true' : 'false'));
                break;
            case "cli"   :
                $this->send($user, (ISCLI ? "<b>Your on command line!</b>" : "Not on command line!"));
                break;
            default      :
                $this->send($user, "not understood, type help for help");
                break;
        }
    }

    protected function connected($user)
    {
        // Do nothing: This is just an echo server, there's no need to track the user.
        // However, if we did care about the users, we would probably have a cookie to
        // parse at this step, would be looking them up in permanent storage, etc.
    }

    protected function closed($user)
    {
        // Do nothing: This is where cleanup would go, in case the user had any sort of
        // open files or other objects associated with them.  This runs after the socket
        // has been closed, so there is no need to clean up the socket itself here.
    }
}

$echo = new echoServer("0.0.0.0", WS_PORT);

try {
    $echo->run();
} catch (Exception $e) {
    $echo->stdout($e->getMessage());
}
