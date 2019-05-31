<?php


namespace Inspector\Models\Context;


class Response extends AbstractContext
{
    /**
     * A dictionary of HTTP headers of the response.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * The HTTP status code of the response.
     *
     * @var mixed
     */
    protected $statusCode;

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function addHeaders(string $name, $value)
    {
        $this->headers[$name] = $value;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setStatusCode($statusCode): Response
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function hasContent(): bool
    {
        return $this->statusCode != null ||
            !empty($this->headers);
    }

    /**
     * Array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'status_code' => $this->statusCode,
            'headers' => $this->headers,
        ];
    }
}