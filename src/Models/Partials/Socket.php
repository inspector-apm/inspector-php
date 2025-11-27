<?php

declare(strict_types=1);

namespace Inspector\Models\Partials;

use Inspector\Models\Model;

use function array_key_exists;

class Socket extends Model
{
    public string $remote_address;
    public bool $encrypted;

    /**
     * Socket constructor.
     */
    public function __construct()
    {
        $this->remote_address = $_SERVER['REMOTE_ADDR'] ?? '';

        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) === true) {
            $this->remote_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        $this->encrypted = isset($_SERVER['HTTPS']);
    }
}
