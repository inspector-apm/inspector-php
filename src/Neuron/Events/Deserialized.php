<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

class Deserialized
{
    public function __construct(public string $class)
    {
    }
}
