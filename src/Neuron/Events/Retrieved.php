<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

use NeuronAI\Chat\Messages\Message;
use NeuronAI\RAG\Document;

class Retrieved
{
    /**
     * @param Document[] $documents
     */
    public function __construct(
        public Message $question,
        public array $documents,
    ) {
    }
}
