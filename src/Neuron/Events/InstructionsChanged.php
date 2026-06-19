<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

class InstructionsChanged
{
    public function __construct(
        public string $previous,
        public string $current
    ) {
    }
}
