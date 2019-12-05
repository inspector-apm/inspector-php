<?php


namespace Inspector\Models\Partials;


use Inspector\Models\Arrayable;

/**
 * Class Http
 * @package Inspector\Models\Partials
 *
 * {
 *  request: object,
 *  response: object,
 *  url: object
 * }
 */
class Http extends Arrayable
{
    /**
     * Http constructor.
     */
    public function __construct()
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $this->request = new Request;
        $this->response = new Response;
        $this->url = new Url;
    }
}
