<?php

class WebSocketController extends Controller
{
    public function actionIndex()
    {
        $echoserver = new EchoServer('0.0.0.0', WS_PORT);

        try {
            $echoserver->run();
        } catch (Exception $e) {
            $echoserver->stdout($e->getMessage());
        }
    }
}

