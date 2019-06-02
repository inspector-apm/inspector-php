<?php

namespace Inspector;


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
    protected $url = 'https://app.inspector.dev/api';

    /**
     * Authentication key.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var string
     */
    protected $transport = 'sync';

    /**
     * Transport options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Environment constructor.
     *
     * @param string $apiKey
     * @throws \InvalidArgumentException
     */
    public function __construct($apiKey)
    {
        $this->setApiKey($apiKey);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setUrl($value): Configuration
    {
        $value = trim($value);

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $this->url = $value;
            return $this;
        }
        throw new \InvalidArgumentException('Invalid URL');
    }

    public function getUrl(): string
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

    public function getApiKey(): string
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

    public function getTransport(): string
    {
        return $this->transport;
    }

    public function setTransport(string $transport): Configuration
    {
        $this->transport = $transport;
        return $this;
    }
}