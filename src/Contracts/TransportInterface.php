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
    public function send($log);
}