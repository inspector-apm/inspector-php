<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

class BranchStart
{
    public function __construct(public readonly string $branchId)
    {
    }
}
