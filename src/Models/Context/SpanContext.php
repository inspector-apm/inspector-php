<?php


namespace Inspector\Models\Context;


use Inspector\Models\Context\Db;
use Inspector\Models\Context\Http;

class SpanContext extends AbstractContext
{
    /**
     * Database interaction details
     *
     * @var Db
     */
    protected $db;

    /**
     * External HTTP request details.
     *
     * @var Http
     */
    protected $http;

    /**
     * SpanContext constructor.
     */
    public function __construct()
    {
        $this->db = new Db();
        $this->http = new Http();
    }

    public function getDb(): Db
    {
        return $this->db;
    }

    public function getHttp(): Http
    {
        return $this->http;
    }

    public function hasContent(): bool
    {
        return $this->db->hasContent() ||
            $this->http->hasContent();
    }

    /**
     * Array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'db' => $this->db->toArray(),
            'http' => $this->http->toArray(),
        ];
    }
}