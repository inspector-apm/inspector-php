<?php

namespace LogEngine\Contracts;


interface TransportInterface
{
    /**
     * Add new log entry to the queue.
     *
     * @param string $log
     * @return TransportInterface
     */
    public function addEntry($log);

    /**
     * Deliver everything on the queue to LOG Engine.
     *
     * @return void
     */
    public function flush();
}