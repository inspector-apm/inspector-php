<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

class SchemaGeneration
{
    public function __construct(public string $class)
    {
    }
}
