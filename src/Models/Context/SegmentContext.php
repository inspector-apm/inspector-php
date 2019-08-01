<?php


namespace Inspector\Models\Context;


class SegmentContext extends AbstractContext
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
     * @var array
     */
    protected $custom = [];

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

    public function getCustom($key = null)
    {
        if(is_null($key)){
            return $this->custom;
        }

        if(array_key_exists($key, $this->custom)){
            return $this->custom[$key];
        }

        return null;
    }

    public function addCustom(string $key, $value): SegmentContext
    {
        $this->custom[$key] = $value;
        return $this;
    }

    public function setCustom($collection): SegmentContext
    {
        $this->custom = $collection;
        return $this;
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
        ] + $this->custom;
    }
}
