<?php


namespace Inspector\Models\Context;


class Socket extends AbstractContext
{
    protected $encrypted = false;

    protected $remoteAddress;

    /**
     * Socket constructor.
     */
    public function __construct()
    {
        $this->remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) === true) {
            $this->remoteAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        $this->encrypted = isset($_SERVER['HTTPS']);
    }

    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    public function setEncrypted($value = true): Socket
    {
        $this->encrypted = $value;
        return $this;
    }

    public function getRemoteAddress(): string
    {
        return $this->remoteAddress;
    }

    public function setRemoteAddress(string $remoteAddress): Socket
    {
        $this->remoteAddress = $remoteAddress;
        return $this;
    }

    public function hasContent(): bool
    {
        return $this->remoteAddress != null;
    }

    /**
     * Array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'remote_address' => $this->remoteAddress,
            'encrypted' => $this->encrypted,
        ];
    }
}