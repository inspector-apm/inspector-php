<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

use NeuronAI\Tools\ToolInterface;

class ToolCalling
{
    public function __construct(public ToolInterface $tool, public readonly bool $fork = false)
    {
    }
}
