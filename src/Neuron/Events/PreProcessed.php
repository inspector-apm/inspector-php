<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

use NeuronAI\Chat\Messages\Message;

class PreProcessed
{
    public function __construct(
        public string $processor,
        public Message $processed
    ) {
    }
}
