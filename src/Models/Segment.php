<?php

namespace Inspector\Models;

use Inspector\Models\Partials\Host;

class Segment extends PerformanceModel
{
    public ?string $model = 'segment';
    public int|float $start;
    public ?string $color = null;
    public ?array $transaction = null;
    public ?Host $host = null;

    /**
     * Span constructor.
     */
    public function __construct(
        Transaction $transaction,
        public string $type = 'process',
        public ?string $label = null
    ) {
        $this->host = new Host();
        $this->transaction = $transaction->only(['name', 'hash', 'timestamp']);
    }

    /**
     * Start the timer.
     */
    public function start(int|float $timestamp = null): Segment
    {
        $initial = \is_null($timestamp) ? \microtime(true) : $timestamp;

        $this->start = \round(($initial - $this->transaction['timestamp']) * 1000, 2);
        parent::start($timestamp);
        return $this;
    }

    public function setColor(string $color): Segment
    {
        $this->color = $color;
        return $this;
    }
}
