<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

use NeuronAI\Chat\Messages\Message;

class Retrieving
{
    public function __construct(
        public Message $question
    ) {
    }
}
