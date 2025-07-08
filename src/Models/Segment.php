<?php

namespace Inspector\Models;

use Inspector\Inspector;
use Inspector\Models\Partials\Host;

class Segment extends PerformanceModel
{
    public ?string $model = 'segment';
    public int|float $start;
    public ?string $color = null;
    public ?array $transaction = null;
    public ?Host $host = null;
    public string $hash;
    public ?string $parent_hash = null;

    /**
     * Reference to the Inspector instance for managing open segments.
     */
    protected ?Inspector $inspector = null;

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
        $this->hash = $this->generateHash();
    }

    /**
     * Set the Inspector instance for managing segment lifecycle.
     */
    public function setInspector(Inspector $inspector): Segment
    {
        $this->inspector = $inspector;
        return $this;
    }

    /**
     * Set the parent segment hash.
     */
    public function setParent(?string $parentHash): Segment
    {
        $this->parent_hash = $parentHash;
        return $this;
    }

    /**
     * Start the timer.
     */
    public function start(int|float|null $timestamp = null): Segment
    {
        $initial = \is_null($timestamp) ? \microtime(true) : $timestamp;

        $this->start = \round(($initial - $this->transaction['timestamp']) * 1000, 2);
        parent::start($timestamp);
        return $this;
    }

    /**
     * End the segment and notify Inspector to remove from open segments.
     */
    public function end(int|float|null $duration = null): Segment
    {
        parent::end($duration);

        // Notify Inspector that this segment has ended
        $this->inspector?->endSegment($this);

        return $this;
    }

    public function setColor(string $color): Segment
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Generate a unique hash for this segment.
     */
    protected function generateHash(): string
    {
        return \hash('sha256', $this->type . $this->label . \microtime(true) . \random_int(1000, 9999));
    }

    /**
     * Get the segment hash.
     */
    public function getHash(): string
    {
        return $this->hash;
    }
}
