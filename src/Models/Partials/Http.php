<?php

namespace Inspector\Models\Partials;

use Inspector\Models\Model;

class Http extends Model
{
    /**
     * Http constructor.
     */
    public function __construct(
        public Request $request = new Request(),
        public Url $url = new Url(),
    ) {
    }
}
