<?php


namespace LogEngine\Models\Context;


abstract class AbstractContext implements \JsonSerializable
{
    public abstract function hasContent(): bool;

    public abstract function toArray(): array;

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->arrayFilterRecursive($this->toArray());
    }

    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }

    protected function arrayFilterRecursive(array $payload)
    {
        foreach ($payload as $key => $item) {
            if (is_array($item)) {
                $payload[$key] = $this->arrayFilterRecursive($item);
            }

            if (!isset($payload[$key]) || empty ($payload[$key])) {
                unset ($payload[$key]);
            }
        }

        return $payload;
    }
}