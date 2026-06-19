<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

use NeuronAI\Chat\Messages\Message;

class MessageSaving
{
    public function __construct(public Message $message)
    {
    }
}
