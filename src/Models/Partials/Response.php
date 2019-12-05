<?php


namespace Inspector\Models\Partials;


use Inspector\Models\Arrayable;

/**
 * Class Response
 * @package Inspector\Models\Partials
 *
 * {
 *  status_code: integer,
 * }
 */
class Response extends Arrayable
{
    /**
     * Response constructor.
     * @param null|integer $statusCode
     */
    public function __construct($statusCode = null)
    {
        $this->status_code = $statusCode;
    }
}
