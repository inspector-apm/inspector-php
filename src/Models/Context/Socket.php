<?php


namespace LogEngine\Models\Context;


class Socket implements \JsonSerializable
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

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'remote_address' => $this->remoteAddress,
            'encrypted' => $this->encrypted,
        ];
    }

    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }
}