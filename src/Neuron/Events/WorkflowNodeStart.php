<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

use NeuronAI\Workflow\WorkflowState;

class WorkflowNodeStart
{
    public function __construct(
        public string $node,
        public WorkflowState $state,
    ) {
    }
}
