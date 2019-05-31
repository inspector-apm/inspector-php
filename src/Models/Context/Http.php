<?php


namespace Inspector\Models\Context;


class Http extends AbstractContext
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
     * Array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'method' => $this->method,
            'status_code' => $this->statusCode,
        ];
    }
}