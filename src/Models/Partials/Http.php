<?php

namespace Inspector\Models\Partials;

use Inspector\Models\Arrayable;

class Http extends Arrayable
{
    /**
     * Http constructor.
     */
    public function __construct()
    {
        $this->request = new Request();
        $this->url = new Url();
    }
}
