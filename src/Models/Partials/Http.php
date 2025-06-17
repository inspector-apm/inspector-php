<?php

namespace Inspector\Models\Partials;

use Inspector\Models\Model;

class Http extends Model
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
