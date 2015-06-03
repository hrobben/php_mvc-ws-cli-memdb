<?php

class WebSocketController extends Controller
{
    public function actionIndex()
    {
        $EchoServer = new EchoServer('0.0.0.0', WS_PORT);

        try {
            $EchoServer->run();
        } catch (Exception $e) {
            $EchoServer->stdout($e->getMessage());
        }
    }
}

