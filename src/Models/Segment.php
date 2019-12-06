<?php

namespace Inspector\Models;


class Segment extends PerformanceModel
{
    const MODEL_NAME = 'segment';

    /**
     * Span constructor.
     *
     * @param string $type
     * @param Transaction $transaction
     */
    public function __construct(Transaction $transaction, $type = 'process', $label = null)
    {
        $this->model = self::MODEL_NAME;
        $this->type = $type;
        $this->label = $label;
        $this->transaction = $transaction->only(['hash', 'timestamp']);
    }

    /**
     * Start the timer.
     *
     * @param null|float $time
     * @return $this
     */
    public function start($time = null)
    {
        $initial = is_null($time) ? microtime(true) : $time;

        $this->start = round(($initial - $this->transaction['timestamp'])*1000, 2);
        return parent::start($time);
    }
}
