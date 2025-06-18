<?php

namespace Inspector\Models\Partials;

use Inspector\Models\Model;

class Url extends Model
{
    public string $protocol;
    public string $port;
    public string $path;
    public string $search;
    public string $full;

    /**
     * Url constructor.
     */
    public function __construct()
    {
        $this->protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $this->port = $_SERVER['SERVER_PORT'] ?? '';
        $this->path = $_SERVER['SCRIPT_NAME'] ?? '';
        $this->search = '?' . ($_SERVER['QUERY_STRING'] ?? '');
        $this->full = isset($_SERVER['HTTP_HOST']) ? $this->protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : '';
    }
}
