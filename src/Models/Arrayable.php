<?php

namespace Inspector\Models;

abstract class Arrayable implements \ArrayAccess, \JsonSerializable
{
    /**
     * Data.
     *
     * @var array
     * @access private
     */
    private array $data = [];

    /**
     * Return a sub-array that contains only the given keys.
     *
     * @param array $keys
     * @return array
     */
    public function only(array $keys): array
    {
        $arr = [];
        foreach ($keys as $key) {
            $arr[$key] = $this->$key;
        }
        return $arr;
    }

    /**
     * Make it compatible to work with php native array functions.
     *
     * @return array
     */
    public function &__invoke(): array
    {
        return $this->data;
    }

    /**
     * Get data by key
     *
     * @param string $key The key data to retrieve
     * @return mixed
     */
    public function &__get(string $key): mixed
    {
        return $this->data[$key];
    }

    /**
     * Assigns a value to the specified data
     */
    public function __set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Whether or not data exists by key
     *
     * @param string $key An data key to check for
     * @access public
     * @return boolean
     * @abstracting ArrayAccess
     */
    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Unsets a data by key
     */
    public function __unset(string $key): void
    {
        unset($this->data[$key]);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (\is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * Unsets an offset.
     */
    public function offsetUnset(mixed $offset): void
    {
        if ($this->offsetExists($offset)) {
            unset($this->data[$offset]);
        }
    }

    /**
     * Returns the value at specified offset.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->offsetExists($offset) ? $this->data[$offset] : null;
    }

    public function __toString()
    {
        return \json_encode($this->jsonSerialize());
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return array data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize(): array
    {
        return \array_filter($this->toArray(), function ($value) {
            // remove NULL, FALSE, empty strings and empty arrays, but keep values of 0 (zero)
            return \is_array($value) ? !empty($value) : \strlen($value ?? '');
        });
    }

    /**
     * Array representation of the object.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
