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
    protected $baseUrl = 'http://logengine.locl/api/collect';

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
     * @param string $apiKey
     * @param string $environment
     * @throws LogEngineException
     */
    public function __construct($apiKey, $environment = null)
    {
        $this->apiKey = $this->validateApiKey($apiKey);
        $this->environment = $environment;
        $this->hostname = gethostname();
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Verify if api key is well formed.
     *
     * @param $value
     * @return string
     * @throws LogEngineException
     */
    private function validateApiKey($value)
    {
        $apiKey = trim($value);

        if (empty($apiKey)) {
            throw new LogEngineException('API key cannot be empty');
        }

        return $apiKey;
    }
}