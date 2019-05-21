<?php


namespace LogEngine\Transaction\Context\Models;


class Url
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
}