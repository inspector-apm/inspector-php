<?php


namespace LogEngine\Models\Context;


class Url implements \JsonSerializable
{
    protected $protocol;

    protected $hostname;

    protected $port;

    protected $path;

    protected $search;

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function setProtocol($protocol): Url
    {
        $this->protocol = $protocol;
        return $this;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function setHostname($hostname): Url
    {
        $this->hostname = $hostname;
        return $this;
    }

    public function getPort(): string
    {
        return $this->port;
    }

    public function setPort($port): Url
    {
        $this->port = $port;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath($path): Url
    {
        $this->path = $path;
        return $this;
    }

    public function getSearch(): string
    {
        return $this->search;
    }

    public function setSearch($search): Url
    {
        $this->search = $search;
        return $this;
    }

    public function hasContent(): bool
    {
        return $this->protocol != null ||
            $this->hostname != null ||
            $this->port != null ||
            $this->path != null ||
            $this->search != null;
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
        return [
            'protocol' => $this->protocol,
            'hostname' => $this->hostname,
            'port' => $this->port,
            'path' => $this->path,
            'search' => $this->search,
        ];
    }

    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }
}