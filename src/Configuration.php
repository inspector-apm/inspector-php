<?php

namespace Inspector;

class Configuration
{
    /**
     * The remote url to send data.
     *
     * @var string
     */
    protected $url = 'https://ingest.inspector.dev';

    /**
     * The API key.
     *
     * @var string|null
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
    protected $version = '3.12.3';

    /**
     * General-purpose options, E.g. we can set the transport proxy.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Configuration constructor.
     *
     * @param null|string $ingestionKey
     * @throws \InvalidArgumentException
     */
    public function __construct($ingestionKey = null)
    {
        if (!empty($ingestionKey)) {
            $this->setIngestionKey($ingestionKey);
        }
    }

    /**
     * Max size of a POST request content.
     */
    public function getMaxPostSize(): int
    {
        return /*OS::isWin() ? 8000 :*/ 65536;
    }

    /**
     * Set the remote url.
     *
     * @param string $value
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setUrl($value): Configuration
    {
        $value = \trim($value);

        if (empty($value)) {
            throw new \InvalidArgumentException('URL can not be empty');
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('URL is invalid');
        }

        $this->url = $value;
        return $this;
    }

    /**
     * Get the remote url.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Verify if api key is well formed.
     *
     * @param string $value
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
     * Get the current API key.
     */
    public function getIngestionKey(): string
    {
        return $this->ingestionKey;
    }

    public function getMaxItems(): int
    {
        return $this->maxItems;
    }

    /**
     * @param int $maxItems
     * @return $this
     */
    public function setMaxItems(int $maxItems): Configuration
    {
        $this->maxItems = $maxItems;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Add a key-value pair to the options list.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addOption($key, $value): Configuration
    {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * Override the entire options.
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
