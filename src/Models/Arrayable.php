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
    private $data = [];

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
    public function &__invoke()
    {
        return $this->data;
    }

    /**
     * Get a data by key
     *
     * @param string $key The key data to retrieve
     * @return mixed
     */
    public function &__get($key)
    {
        return $this->data[$key];
    }

    /**
     * Assigns a value to the specified data
     *
     * @param string $key The data key to assign the value to
     * @param mixed $value The value to set
     * @access public
     */
    public function __set($key, $value)
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
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Unsets a data by key
     *
     * @param string $key The key to unset
     * @access public
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Assigns a value to the specified offset.
     *
     * @param string $offset The offset to assign the value to
     * @param mixed $value The value to set
     * @abstracting ArrayAccess
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (\is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Whether an offset exists.
     *
     * @param string $offset An offset to check for
     * @return boolean
     * @abstracting ArrayAccess
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * Unsets an offset.
     *
     * @param string $offset The offset to unset
     * @abstracting ArrayAccess
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->data[$offset]);
        }
    }

    /**
     * Returns the value at specified offset.
     *
     * @param string $offset The offset to retrieve
     * @return mixed
     * @abstracting ArrayAccess
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->data[$offset] : null;
    }

    /**
     * Json String representation of the object.
     *
     * @return false|string
     */
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
