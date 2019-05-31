<?php


namespace Inspector\Models\Context;


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

    protected function arrayFilterRecursive(array $payload)
    {
        $filtered = [];

        foreach ($payload as $key => $item) {
            if (is_array($item)) {
                $item = $this->arrayFilterRecursive($item);
            }

            if (!empty($item)) {
                $filtered[$key] = $item;
            }
        }

        return $filtered;
    }

    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }
}