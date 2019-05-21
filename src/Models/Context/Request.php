<?php


namespace LogEngine\Models\Context;


class Request implements \JsonSerializable
{
    /**
     * @var Url
     */
    protected $url;

    /**
     * @var Socket
     */
    protected $socket;

    /**
     * @var string
     */
    protected $httpVersion;

    /**
     * @var string
     */
    protected $method;

    /**
     * A dictionary of HTTP headers of the request.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * A dictionary of HTTP cookies of the request.
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * A dictionary of Url Encoded Form Parameters of the request.
     *
     * @var array
     */
    protected $postParams = [];

    /**
     * @var string
     */
    protected $rawBody;

    /**
     * Request constructor.
     */
    public function __construct()
    {
        $this->url = new Url();
        $this->socket = new Socket();
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getSocket()
    {
        return $this->socket;
    }

    public function getHttpVersion()
    {
        return $this->httpVersion;
    }

    public function setHttpVersion($httpVersion)
    {
        $this->httpVersion = $httpVersion;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod(string $method)
    {
        $this->method = $method;
    }

    /**
     * @return array
     */
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

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function setCookies(array $cookies)
    {
        $this->cookies = $cookies;
    }

    public function addCookie(string $name, $value)
    {
        $this->cookies[$name] = $value;
    }

    public function getPostParams(): array
    {
        return $this->postParams;
    }

    public function setPostParams(array $postParams)
    {
        $this->postParams = $postParams;
    }

    public function addFormUrlEncodedParameter(string $key, $value): Request
    {
        $this->postParams[$key] = $value;
        return $this;
    }

    public function getRawBody()
    {
        return $this->rawBody;
    }

    public function setRawBody($rawBody)
    {
        $this->rawBody = $rawBody;
    }

    public function getBody()
    {
        if(!empty($this->postParams)){
            return $this->postParams;
        } elseif (isset($this->rawBody)) {
            return $this->rawBody;
        } else {
            return null;
        }
    }

    public function hasContent(): bool
    {
        return $this->url->hasContent() ||
            $this->socket->hasContent() ||
            $this->httpVersion != null ||
            $this->method != null ||
            !empty($this->headers) ||
            !empty($this->cookies) ||
            !empty($this->postParams) ||
            $this->rawBody != null;
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
            'socket' => $this->socket,
            'http_version' => $this->httpVersion,
            'method' => $this->method,
            'headers' => $this->headers,
            'cookies' => $this->cookies,
            'post_params' => $this->postParams,
            'raw_body' => $this->rawBody,
        ];
    }

    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }
}