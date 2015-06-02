<?php

class ConnectModel extends Model
{
    public $echo;

    public function connect()
    {
        $this->echo = new WebSocketController(USER_IP, "9000");
    }

}