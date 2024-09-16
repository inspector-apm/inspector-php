<?php

namespace Inspector;


class Configuration
{
    /**
     * Remote endpoint to send data.
     *
     * @var string
     */
    protected $url = 'https://ingest.inspector.dev';

    /**
     * Authentication key.
     *
     * @var string
     */
    protected $ingestionKey;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * Max numbers of items to collect in a single session.
     *
     * @var int
     */
    protected $maxItems = 100;

    /**
     * @var string
     */
    protected $transport = 'async';

    /**
     * @var string
     */
    protected $version = '3.8.3';

    /**
     * Transport options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Environment constructor.
     *
     * @param null|string $ingestionKey
     * @throws \InvalidArgumentException
     */
    public function __construct($ingestionKey = null)
    {
        if(!empty($ingestionKey)) {
            $this->setIngestionKey($ingestionKey);
        }
    }

    /**
     * Max size of a POST request content.
     *
     * @return  integer
     */
    public function getMaxPostSize(): int
    {
        return OS::isWin() ? 8000 : 65536;
    }

    /**
     * Set ingestion url.
     *
     * @param string $value
     * @return Configuration
     */
    public function setUrl($value): Configuration
    {
        $value = \trim($value);

        if (empty($value)) {
            throw new \InvalidArgumentException('Invalid URL');
        }

        $this->url = $value;
        return $this;
    }

    /**
     * Get ingestion endpoint.
     *
     * @return string
     */
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
    public function setIngestionKey($value): Configuration
    {
        $value = \trim($value);

        if (empty($value)) {
            throw new \InvalidArgumentException('Ingestion key cannot be empty');
        }

        $this->ingestionKey = $value;
        return $this;
    }

    /**
     * Get current API key.
     *
     * @return string
     */
    public function getIngestionKey(): string
    {
        return $this->ingestionKey;
    }

    /**
     * @return int
     */
    public function getMaxItems(): int
    {
        return $this->maxItems;
    }

    /**
     * @param int $maxItems
     * @return Configuration
     */
    public function setMaxItems(int $maxItems): Configuration
    {
        $this->maxItems = $maxItems;
        return $this;
    }

    /**
     * Transport options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Add a new entry in the options list.
     *
     * @param string $key
     * @param $value
     * @return Configuration
     */
    public function addOption($key, $value): Configuration
    {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * Override the transport options.
     *
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options): Configuration
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Check if data transfer is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return isset($this->ingestionKey) && \is_string($this->ingestionKey) && $this->enabled;
    }

    /**
     * Enable/Disable data transfer.
     *
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled(bool $enabled): Configuration
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Get current transport method.
     *
     * @return string
     */
    public function getTransport(): string
    {
        return $this->transport;
    }

    /**
     * Set the preferred transport method.
     *
     * @param string $transport
     * @return $this
     */
    public function setTransport(string $transport): Configuration
    {
        $this->transport = $transport;
        return $this;
    }

    /**
     * Get the package version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set the package version.
     *
     * @param string $value
     * @return $this
     */
    public function setVersion($value): Configuration
    {
        $this->version = $value;
        return $this;
    }
}
