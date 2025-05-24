<?php

namespace App\Service;


use React\EventLoop\Loop;
use React\Socket\Connector;
use React\Socket\ConnectionInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class InternalWebSocketServer
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = new $logger('websocket');
        $this->logger->pushHandler(new StreamHandler('var/log/websocket.log', Logger::DEBUG));
    }

    public function startServer()
    {

        $loop = Loop::get();
        $connector = new Connector($loop);

        // replace with actual URL
        try {
            // Connecting to the external WebSocket server
            $connector->connect('127.0.0.1:8000')->then(
                function (ConnectionInterface $conn) use ($loop) {
                    //$conn->pipe(new React\Stream\WritableResourceStream(STDOUT));
                    //$conn->write("Hello World!\n");
                    $loop->addPeriodicTimer(30, function () use ($conn) {
                        $this->logger->info("Sending ping to keep connection alive.\n");
                        $conn->write('ping'); // Send a ping every 30 seconds
                    });
                    $this->logger->info("New connection: {$conn->getRemoteAddress()}");

                    // Send a welcome message to the client
                    $conn->write('Welcome to the Internal WebSocket Server!\n');
                    $conn->on('error', function ($e) {
                        $this->logger->error("Error on Internal connection: {$e->getMessage()}");
                        $this->logger->info("Connection error", ['err' => $e, 'trace' => $e->getTrace()]);
                    });

                    // Handle incoming messages
                    $conn->on('data', function ($data) use ($conn) {
                        $this->logger->info(
                            "Received data from internal WS",
                            ['data' => $data]
                        );
                        $conn->write("Echo: {$data}");
                        //communicate with client side dedicated event listener

                    });

                    // Handle connection closure
                    $conn->on('close', function ($code = null, $reason = null) use ($loop) {
                        $this->logger->info("Connection closed ", [$code, $reason, $this]);

                        //$loop->stop(); // Stop the loop when the connection is closed

                    });
                }
            )->catch(function (\Exception $e) {
                $this->logger->info("Connection error", ['err' => $e, 'trace' => $e->getTrace()]);
            });


            // Run the event loop
            $loop->run();
        } catch (\Exception $e) {
            $this->logger->error("Failed to connect to WebSocket: " . $e->getMessage(), $e->getTrace());
            //$loop->stop(); // Stop the loop in case of error
        }
    }
}
