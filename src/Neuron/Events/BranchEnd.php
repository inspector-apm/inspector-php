<?php

declare(strict_types=1);

namespace Inspector\Neuron\Events;

class BranchEnd
{
    public function __construct(public readonly string $branchId)
    {
    }
}
