<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

use NeuronAI\Tools\ToolInterface;

class ToolsBootstrapped
{
    /**
     * @param ToolInterface[] $tools
     * @param string[] $guidelines
     */
    public function __construct(public array $tools, public array $guidelines = [])
    {
    }
}
