<?php


namespace LogEngine\Models;


class Http implements \JsonSerializable
{
    protected $url;

    protected $method;

    protected $statusCode;

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl($url): Http
    {
        $this->url = $url;
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod($method): Http
    {
        $this->method = $method;
        return $this;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setStatusCode($statusCode): Http
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function hasContent(): bool
    {
        return $this->url != null ||
            $this->method != null ||
            $this->statusCode != null;
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
            'url' => $this->url,
            'method' => $this->method,
            'status_code' => $this->statusCode,
        ];
    }

    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }
}