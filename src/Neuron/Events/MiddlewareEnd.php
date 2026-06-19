<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

use NeuronAI\Workflow\Middleware\WorkflowMiddleware;

class MiddlewareEnd
{
    public function __construct(public WorkflowMiddleware $middleware)
    {
    }
}
