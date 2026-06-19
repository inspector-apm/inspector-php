<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

class Deserializing
{
    public function __construct(public string $class)
    {
    }
}
