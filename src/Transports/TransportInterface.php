<?php

namespace Inspector\Transports;

use Inspector\Models\Model;

interface TransportInterface
{
    /**
     * Add an Array able entity in the queue.
     */
    public function addEntry(Model $model): TransportInterface;

    /**
     * Clean the internal queue.
     */
    public function resetQueue(): TransportInterface;

    /**
     * Send data to Inspector.
     *
     * This method is invoked after your application has sent
     * the response to the client.
     *
     * So this is the right place to perform the data transfer.
     */
    public function flush(): TransportInterface;
}
