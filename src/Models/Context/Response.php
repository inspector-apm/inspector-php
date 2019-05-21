<?php


namespace LogEngine\Models\Context;


class Response implements \JsonSerializable
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
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'status_code' => $this->statusCode,
            'headers' => $this->headers,
        ];
    }

    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }
}