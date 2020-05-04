<?php


namespace Inspector\Transports;


interface TransportInterface
{
    public function flush();

    public function getApiHeaders();
}
