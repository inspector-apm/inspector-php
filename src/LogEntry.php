<?php

namespace LogEngine;


use LogEngine\Contracts\LogEntryInterface;

class LogEntry implements LogEntryInterface
{
    /**
     * Data.
     *
     * @var array
     */
    protected $record;

    /**
     * LogMessage constructor.
     *
     * @param array $record
     */
    public function __construct(array $record)
    {
        $this->record = array_merge(
            array(
                'timestamp' => round(microtime(true) * 1000),
                'type' => 'log',
            ),
            $record
        );
    }

    /**
     * @return string
     */
    public function getLevel()
    {
        return $this->record['level'];
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->record['message'];
    }

    /**
     * @return integer  Timestamp in milliseconds
     */
    public function getMicrosecondTimestamp()
    {
        return $this->record['timestamp'];
    }

    /**
     * @return array  Additional log data
     */
    public function getContext()
    {
        return $this->record['context'];
    }

    /**
     * Merge params in current object.
     *
     * @param array $params
     * @return $this
     */
    public function merge(array $params)
    {
        $this->record = array_merge($this->record, $params);
        return $this;
    }

    /**
     * Array representation of the log.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return array data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->record;
    }

    /**
     * String representation of the log.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }
}