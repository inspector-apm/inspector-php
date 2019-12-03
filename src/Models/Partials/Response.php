<?php


namespace Inspector\Models\Partials;


use Inspector\Models\Arrayable;

class Response extends Arrayable
{
    public function __construct($statusCode = null)
    {
        $this->status_code = $statusCode;
    }
}
