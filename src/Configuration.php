<?php

namespace Inspector;

class Configuration
{
    /**
     * The remote url to send data.
     */
    protected string $url = 'https://ingest.inspector.dev';

    protected string $ingestionKey;

    protected bool $enabled = true;

    /**
     * Max numbers of items to collect in a single session.
     */
    protected int $maxItems = 100;

    protected string $transport = 'async';

    protected ?string $version = '3.16.1';

    /**
     * General-purpose options, E.g., we can set the transport proxy.
     */
    protected array $options = [];

    /**
     * Configuration constructor.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(?string $ingestionKey = null)
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
     * @throws \InvalidArgumentException
     */
    public function setUrl(string $value): Configuration
    {
        $value = \trim($value);

        if (empty($value)) {
            throw new \InvalidArgumentException('URL can not be empty');
        }

        if (\filter_var($value, \FILTER_VALIDATE_URL) === false) {
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
     * Verify if api key is well-formed.
     *
     * @param string $value
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setIngestionKey(string $value): Configuration
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
    public function addOption(string $key, mixed $value): Configuration
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
        return isset($this->ingestionKey) && $this->enabled;
    }

    /**
     * Enable/Disable data transfer.
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
     */
    public function setTransport(string $transport): Configuration
    {
        $this->transport = $transport;
        return $this;
    }

    /**
     * Get the package version.
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Set the package version.
     */
    public function setVersion(?string $value): Configuration
    {
        $this->version = $value;
        return $this;
    }
}
