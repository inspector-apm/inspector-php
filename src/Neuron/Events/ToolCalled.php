<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

use NeuronAI\Tools\ToolInterface;

class ToolCalled
{
    public function __construct(public ToolInterface $tool)
    {
    }
}
