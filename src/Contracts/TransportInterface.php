<?php

namespace LogEngine\Contracts;


interface TransportInterface
{
    /**
     * Add new log entry to the queue.
     *
     * @param array $log
     * @return TransportInterface
     */
    public function addEntry(array $log);

    /**
     * Deliver everything on the queue to LOG Engine.
     *
     * @return void
     */
    public function flush();
}