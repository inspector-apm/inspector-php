<?php

namespace Inspector\Models;

abstract class PerformanceModel extends Model
{
    use HasContext;

    /**
     * Start the timer.
     *
     * @param null|float $timestamp
     * @return $this
     */
    public function start($timestamp = null)
    {
        $this->timestamp = \is_null($timestamp) ? \microtime(true) : $timestamp;
        return $this;
    }

    /**
     * Stop the timer and calculate duration.
     *
     * @param float|null $duration milliseconds
     * @return PerformanceModel
     */
    public function end($duration = null)
    {
        $this->duration = $duration ?? \round((\microtime(true) - $this->timestamp) * 1000, 2); // milliseconds
        return $this;
    }
}
