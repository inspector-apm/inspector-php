<?php

namespace LogEngine\Contracts;


interface TransportInterface
{
    /**
     * Add new log entry to the queue.
     *
     * @param LogEntryInterface $log
     * @return TransportInterface
     */
    public function addEntry(LogEntryInterface $log): TransportInterface;

    /**
     * Deliver everything on the queue to LOG Engine.
     *
     * @return void
     */
    public function flush();
}