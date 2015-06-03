<?php

class EchoServer extends WebSocketServer
{
    protected $out = 'slug';
    protected $id;

    protected function process($user, $message)
    {
        $this->maxBufferSize = 1048576;
        switch ($message) {
            case 'hello' :
                $this->send($user, 'hello human');
                break;
            case 'help'   :
                $this->send($user, 'help: <br> articles  => to show all articles.<br>
xml => to make output shown in XML on/off.<br>json => output in JSON on/off.<br>article=1 => gives single article id=1');
                break;
            case (substr($message, 0, 8) === 'article=' && strlen($message) > 8):
                $this->id = substr($message, -1 * (strlen($message) - 8));
                $send = new CliController('', '');
                $view = $send->actionView($this->id, $this->out);
                $this->send($user, $view);
                break;
            case 'articles'   :
                $send = new CliController('', '');
                $view = $send->actionIndex($this->out);
                $this->send($user, $view);
                break;
            case 'xml'   :
                $this->out = ($this->out === 'xml') ? '' : 'xml';
                $this->send($user, 'xml = ' . $this->out);
                break;
            case 'json'   :
                $this->out = ($this->out === 'json') ? '' : 'json';
                $this->send($user, 'json = ' . $this->out);
                break;
            case 'cli'   :
                $this->send($user, (PHP_SAPI === 'cli' ? '<b>Your on command line!</b>' : 'Not on command line!'));
                break;
            default      :
                $this->send($user, 'not understood, type help for help');
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
