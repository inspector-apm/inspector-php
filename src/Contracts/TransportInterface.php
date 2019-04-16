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
    public function send($log);
}