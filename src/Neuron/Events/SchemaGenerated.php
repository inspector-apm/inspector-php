<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

class SchemaGenerated
{
    /**
     * @param array<string, mixed> $schema
     */
    public function __construct(public string $class, public array $schema)
    {
    }
}
