<?php

namespace LogEngine;


class Configuration
{
    /**
     * Max size of a POST request content.
     *
     * @var integer
     */
    const MAX_POST_LENGTH = 65536;

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
     * Transport options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * @var string
     */
    protected $hostname;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * Environment constructor.
     *
     * @param string $apiKey
     * @throws \InvalidArgumentException
     */
    public function __construct($apiKey)
    {
        $this->setApiKey($apiKey);
        $this->hostname = gethostname();
        $this->environment = getenv('LOGENGINE_ENV');
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

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function addOption($key, $value): Configuration
    {
        $this->options[$key] = $value;
        return $this;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function setHostname(string $hostname): Configuration
    {
        $this->hostname = $hostname;
        return $this;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function setEnvironment(string $environment): Configuration
    {
        $this->environment = $environment;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): Configuration
    {
        $this->enabled = $enabled;
        return $this;
    }
}