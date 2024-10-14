<?php

namespace Yabasi\WebSocket;

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Yabasi\WebSocket\ChatWebSocketServer;

class WebSocketServerCommand
{
    public function handle(): void
    {
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new ChatWebSocketServer()
                )
            ),
            8080
        );

        echo "WebSocket server started on port 8080\n";
        $server->run();
    }
}