<?php

namespace Inspector\Models;

abstract class PerformanceModel extends Model
{
    public int|float $timestamp;
    public int|float|null $duration = null;

    /**
     * Start the timer.
     */
    public function start(int|float|null $timestamp = null): PerformanceModel
    {
        $this->timestamp = \is_null($timestamp) ? \microtime(true) : $timestamp;
        return $this;
    }

    /**
     * Stop the timer and calculate the duration.
     */
    public function end(int|float|null $duration = null): PerformanceModel
    {
        $this->duration = $duration ?? \round((\microtime(true) - $this->timestamp) * 1000, 2); // milliseconds
        return $this;
    }
}
