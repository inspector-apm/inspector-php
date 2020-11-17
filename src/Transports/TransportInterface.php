<?php


namespace Inspector\Transports;


use Inspector\Models\Arrayable;

interface TransportInterface
{
    /**
     * Add an Arrayable entity in the queue.
     *
     * @param \Inspector\Models\Arrayable $entry
     * @return mixed
     */
    public function addEntry(Arrayable $entry);

    /**
     * Send data to Inspector.
     *
     * This method is invoked after your application has sent
     * the response to the client.
     *
     * So this is the right place to perform the data transfer.
     *
     * @return mixed
     */
    public function flush();
}
