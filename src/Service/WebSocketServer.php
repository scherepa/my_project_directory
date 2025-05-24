<?php

namespace App\Service;


use React\EventLoop\Loop;
use React\Socket\Connector;
use React\Socket\ConnectionInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class WebSocketServer
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

        // URL of the external WSS server
        $wssUrl = 'stream.binance.com:9443/ws/btcusdt@bookTicker/';
        //'wss://stream.binance.com:9443/ws/btcusdt@bookTicker';
        //'wss://api.binance.com/api/v3/ticker/bookTicker';
        // replace with actual URL
        $this->logger->info('Connecting to WebSocket: ' . $wssUrl);
        $connector->connect($wssUrl)->then(
            function (ConnectionInterface $conn) {
                $this->logger->info("New connection: {$conn->getRemoteAddress()}");

                // Send a welcome message to the client
                //$conn->write('ping');
                /*$conn->write('"data":"HTTP/1.1 400 Bad Request\r\nServer: awselb/2.0\r\nDate: Thu, 10 Apr 2025 03:46:06 GMT\r\nContent-Type: text/html\r\nContent-Length: 122\r\nConnection: close\r\n\r\n<html>\r\n<head><title>400 Bad Request</title></head>\r\n<body>\r\n<center><h1>400 Bad Request</h1></center>\r\n</body>\r\n</html>\r\n"');*/

                // Handle incoming messages
                $conn->on('data', function ($data) use ($conn) {
                    $this->logger->info(
                        "Received data from external WSS",
                        ['data' => $data]
                    );

                    $conn->on('data', function ($chunk) {
                        echo $chunk;
                    });
                    // $conn->write("Echo: {$data}");
                    //here I want to analyze that data is ok for db
                    // Process the data (e.g.,  validate for database entity )
                    $this->processData($data);
                    //then i want to dispatch event that will send updates to internal server
                    // and create or update data in db in dedicated event listener

                });
                $conn->on('error', function ($e) {
                    $this->logger->error("Error on External connection: {$e->getMessage()}");
                });

                // Handle connection closure
                $conn->on('close', function ($code = null, $reason = null) {
                    $this->logger->info("Connection closed", ['code' => $code, 'reason' => $reason]);
                });
            }
        )->catch(function (\Exception $e) use ($loop) {
            // Handle errors (connection failures, etc.)
            $this->logger->error("Failed to connect to External WebSocket: " . $e->getMessage());
            //$loop->stop(); // Stop the loop when the connection is closed
        });

        // Run the event loop
        $loop->run();
    }

    private function processData(string $data)
    {
        // You can analyze the data here and perform database operations
        // For example, update the database or dispatch events for internal updates
        // This is just an example, you can adjust it according to your needs
        // You can dispatch Symfony events, or process the data in a listener
        $this->logger->info('Processing data for database update');

        // Example: Dispatch an event to handle updates
        // $event = new SomeEvent($data);
        // $this->eventDispatcher->dispatch($event);
    }
}
