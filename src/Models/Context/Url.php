<?php


namespace Inspector\Models\Context;


class Url extends AbstractContext
{
    protected $protocol;

    protected $hostname;

    protected $port;

    protected $path;

    protected $search;

    protected $full;

    /**
     * Url constructor.
     */
    public function __construct()
    {
        $this->protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $this->hostname = $_SERVER['SERVER_NAME'] ?? '';
        $this->port = $_SERVER['SERVER_PORT'] ?? '';
        $this->path = $_SERVER['SCRIPT_NAME'] ?? '';
        $this->search = '?' . (($_SERVER['QUERY_STRING'] ?? '') ?? '');
        $this->full = isset($_SERVER['HTTP_HOST']) ? $this->protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : '';
    }

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

    public function getFull(): string
    {
        return $this->full;
    }

    public function setFull(string $full): Url
    {
        $this->full = $full;
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
     * Array representation.
     *
     * @return array
     */
    public function toArray(): array
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