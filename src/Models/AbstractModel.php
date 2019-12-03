<?php


namespace Inspector\Models;


abstract class AbstractModel extends Arrayable
{
    /**
     * Add contextual information.
     *
     * @param string $label
     * @param mixed $data
     * @return $this
     */
    public function addContext($label, $data)
    {
        $this->context[$label] = $data;
        return $this;
    }

    /**
     * Start the timer.
     *
     * @param null|float $time
     * @return $this
     */
    public function start($time = null)
    {
        $this->timestamp = is_null($time) ? microtime(true) : $time;
        return $this;
    }

    public function end($duration = null): AbstractModel
    {
        $this->duration = $duration ?? round((microtime(true) - $this->timestamp)*1000, 2); // milliseconds
        return $this;
    }
}
