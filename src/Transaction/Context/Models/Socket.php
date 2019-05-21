<?php


namespace LogEngine\Transaction\Context\Models;


class Socket
{
    protected $encrypted = false;

    protected $remoteAddress;

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
}