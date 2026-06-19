<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

use NeuronAI\Workflow\WorkflowState;

class WorkflowNodeEnd
{
    public function __construct(
        public string $node,
        public WorkflowState $state
    ) {
    }
}
