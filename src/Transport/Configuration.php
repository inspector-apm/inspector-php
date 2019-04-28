<?php

namespace LogEngine\Transport;


class Configuration
{
    /**
     * Remote web service endpoint to store log entries.
     *
     * @var string
     */
    protected $url;

    /**
     * Authentication key.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Environment constructor.
     *
     * @param string|null $url
     * @param string|null $apiKey
     * @throws \InvalidArgumentException
     */
    public function __construct($url, $apiKey)
    {
        $this->setUrl($url);
        $this->setApiKey($apiKey);
    }

    /**
     * @param $value
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setUrl($value)
    {
        $value = trim($value);

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $this->url = $value;
            return $this;
        }
        throw new \InvalidArgumentException('Invalid URL');
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Verify if api key is well formed.
     *
     * @param $value
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setApiKey($value)
    {
        $apiKey = trim($value);

        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API key cannot be empty');
        }

        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }
}