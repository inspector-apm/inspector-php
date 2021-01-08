<?php


namespace Inspector\Models\Partials;


use Inspector\Models\Arrayable;

class Request extends Arrayable
{
    /**
     * Request constructor.
     */
    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];

        $this->version = substr($_SERVER['SERVER_PROTOCOL'], strpos($_SERVER['SERVER_PROTOCOL'], '/'));

        $this->socket = new Socket();

        $this->cookies = $_COOKIE;

        if (function_exists('apache_request_headers')) {
            $this->headers = array_map(function ($value) {
                return addslashes($value);
            }, apache_request_headers());
        }
    }
}
