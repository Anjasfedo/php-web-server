<?php

namespace Koneksi;

use Koneksi\Request;
use Koneksi\Exception;

class Server
{
    protected $host = null;
    protected $port = null;
    protected $socket = null;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = (int) $port;

        $this->createSocket();

        $this->bind();
    }

    protected function createSocket()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, 0);
    }

    protected function bind()
    {
        if (!socket_bind($this->socket, $this->host, $this->port)) {
            throw new Exception(("Can't Bind: " . $this->host . ":" . $this->port . " - " . socket_strerror(socket_last_error())));
        }
    }


    public function listen($callback)
    {
        if (!is_callable($callback)) {
            throw new Exception("Argument should callable");
        }

        while (true) {
            socket_listen($this->socket);
    
            // Accept client connection
            if (($client = socket_accept($this->socket)) === false) {
                // Error accepting client connection, continue to the next iteration
                continue;
            }
    
            // Read request from client
            $request = Request::withHeaderString(socket_read($client, 1024));
    
            // Handle request and get response
            $response = call_user_func($callback, $request);
    
            // Check if the response is valid
            if (!$response || !$response instanceof Response) {
                // If response is invalid or not an instance of Response, return a 404 error response
                $response = Response::error(404);
            }
    
            // Convert response to string
            $responseString = (string) $response;
    
            // Send response to client
            socket_write($client, $responseString, strlen($responseString));
    
            // Close client socket connection
            socket_close($client);
        }
    }
}
