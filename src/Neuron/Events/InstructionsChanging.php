<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

class InstructionsChanging
{
    public function __construct(
        public string $instructions
    ) {
    }
}
