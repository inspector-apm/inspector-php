<?php

namespace Inspector\Models\Partials;

use Inspector\Models\Model;

class Request extends Model
{
    public ?string $method = null;
    public ?string $version = null;
    public ?Socket $socket = null;
    public ?array $cookies = null;
    public ?array $headers = null;

    /**
     * Request constructor.
     */
    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? null;

        $this->version = isset($_SERVER['SERVER_PROTOCOL'])
            ? \substr($_SERVER['SERVER_PROTOCOL'], \strpos($_SERVER['SERVER_PROTOCOL'], '/'))
            : 'unknown';

        $this->socket = new Socket();

        $this->cookies = $_COOKIE;

        if (\function_exists('apache_request_headers')) {
            $h = apache_request_headers();

            if (\array_key_exists('sec-ch-ua', $h)) {
                unset($h['sec-ch-ua']);
            }

            $this->headers = $h;
        }
    }
}
