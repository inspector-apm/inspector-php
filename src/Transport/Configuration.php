<?php

namespace LogEngine\Transport;


use LogEngine\Exceptions\LogEngineException;

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
     * Environment name.
     *
     * @var string
     */
    protected $environment;

    /**
     * Machine's readable name.
     *
     * @var string
     */
    protected $hostname;

    /**
     * Environment constructor.
     *
     * @param string|null $url
     * @param string|null $apiKey
     * @param string|null $environment
     * @throws LogEngineException
     */
    public function __construct($url, $apiKey, $environment)
    {
        $this->setUrl($url);
        $this->setApiKey($apiKey);
        $this->setEnvironment($environment);
        $this->setHostname(gethostname());
    }

    /**
     * @param $value
     * @return $this
     * @throws LogEngineException
     */
    public function setUrl($value)
    {
        $value = trim($value);

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $this->url = $value;
            return $this;
        }
        throw new LogEngineException('Invalid URL');
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
     * @throws LogEngineException
     */
    public function setApiKey($value)
    {
        $apiKey = trim($value);

        if (empty($apiKey)) {
            throw new LogEngineException('API key cannot be empty');
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

    /**
     * @param string $value
     * @return $this
     */
    public function setEnvironment($value)
    {
        $this->environment = $value;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setHostname($value)
    {
        $this->hostname = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }
}