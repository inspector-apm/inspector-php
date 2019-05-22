<?php


namespace LogEngine\Models\Context;


use LogEngine\Models\Db;
use LogEngine\Models\Http;

class SpanContext implements \JsonSerializable
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
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $content = [
            'db' => $this->db,
            'http' => $this->http,
        ];

        return array_filter($content, function ($key, $value) {
            return !is_null($value);
        });
    }

    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }
}