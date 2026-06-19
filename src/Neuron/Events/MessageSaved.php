<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

use NeuronAI\Chat\Messages\Message;

class MessageSaved
{
    public function __construct(public Message $message)
    {
    }
}
