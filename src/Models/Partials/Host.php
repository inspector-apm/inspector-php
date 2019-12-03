<?php


namespace Inspector\Models\Partials;


use Inspector\Models\Arrayable;

class Host extends Arrayable
{
    /**
     * Host constructor.
     */
    public function __construct()
    {
        $this->hostname = gethostname();
        $this->ip = gethostbyname(gethostname());
    }
}
