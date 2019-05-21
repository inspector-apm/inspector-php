<?php

namespace LogEngine\Transport;


class TransportConfiguration
{
    /**
     * Max size of a POST request content.
     *
     * @var integer
     */
    const MAX_POST_LENGTH = 65536;  // 1024 * 64

    /**
     * Remote endpoint to send data.
     *
     * @var string
     */
    protected $url = 'https://www.app.logengine.dev/api';

    /**
     * Authentication key.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Environment constructor.
     *
     * @param string|null $apiKey
     * @param string|null $url
     * @throws \InvalidArgumentException
     */
    public function __construct($apiKey, $url = null)
    {
        $this->setApiKey($apiKey);

        if(!is_null($url)){
            $this->setUrl($url);
        }
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