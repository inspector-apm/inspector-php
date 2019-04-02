<?php

namespace LogEngine\Contracts;


interface LogEntryInterface extends \JsonSerializable
{
    /**
     * @return string
     */
    public function getLevel();

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return integer  Timestamp in milliseconds
     */
    public function getMicrosecondTimestamp();

    /**
     * @return array  Additional log data
     */
    public function getContext();

    /**
     * Array representation of the log.
     *
     * @return array
     */
    public function toArray(): array;
}