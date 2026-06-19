<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

class Validating
{
    public function __construct(public string $class, public string $json)
    {
    }
}
