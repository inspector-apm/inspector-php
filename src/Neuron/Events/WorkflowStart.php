<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

use NeuronAI\Workflow\NodeInterface;

class WorkflowStart
{
    /**
     * @param NodeInterface[] $eventNodeMap
     */
    public function __construct(public array $eventNodeMap)
    {
    }
}
