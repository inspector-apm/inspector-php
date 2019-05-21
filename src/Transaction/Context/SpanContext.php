<?php


namespace LogEngine\Transaction\Context;


use LogEngine\Transaction\Db;
use LogEngine\Transaction\Http;

class SpanContext
{
    protected $db;

    protected $http;

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
}