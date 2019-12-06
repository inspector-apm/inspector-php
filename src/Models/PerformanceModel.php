<?php


namespace Inspector\Models;


abstract class PerformanceModel extends Arrayable
{
    use HasContext;

    /**
     * Start the timer.
     *
     * @param null|float $time
     * @return $this
     */
    public function start($time = null): PerformanceModel
    {
        $this->timestamp = is_null($time) ? microtime(true) : $time;
        return $this;
    }

    /**
     * Stop the timer and calculate duration.
     *
     * @param null $duration
     * @return PerformanceModel
     */
    public function end($duration = null): PerformanceModel
    {
        $this->duration = $duration ?? round((microtime(true) - $this->timestamp)*1000, 2); // milliseconds
        return $this;
    }
}
