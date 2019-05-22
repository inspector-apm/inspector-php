<?php


namespace LogEngine\Models;


use LogEngine\Models\Context\SpanContext;

class Span implements \JsonSerializable
{
    const MODEL_NAME = 'span';

    /**
     * The Transaction that own the span.
     *
     * @var Transaction
     */
    protected $transaction;

    /**
     * Segmenting span type.
     *
     * @var string
     */
    protected $type;

    /**
     * @var float
     */
    protected $start;

    /**
     * Number of milliseconds until Span ends.
     *
     * @var float
     */
    protected $duration = 0.0;

    /**
     * PHP backtrace.
     *
     * @var array
     */
    protected $backtrace = [];

    /**
     * Limit the number of stack frames returned.
     *
     * @var int
     */
    protected $backtraceLimit = 0;

    /**
     * @var SpanContext
     */
    protected $context;

    /**
     * Span constructor.
     *
     * @param string $type
     * @param Transaction $transaction
     */
    public function __construct($type, Transaction $transaction)
    {
        $this->type = $type;
        $this->transaction = $transaction;
        $this->context = new SpanContext();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function start(): Span
    {
        $this->start = microtime(true);
        return $this;
    }

    public function end($duration = null): Span
    {
        $this->duration = $duration ?? round((microtime(true) - $this->start)*1000, 2); // milliseconds
        $this->backtrace = debug_backtrace($this->backtraceLimit);
        return $this;
    }

    public function getContext(): SpanContext
    {
        return $this->context;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'model' => self::MODEL_NAME,
            'transaction' => $this->transaction->getHash(),
            'type' => $this->type,
            'start' => $this->start,
            'duration' => $this->duration,
            'context' => $this->context->jsonSerialize(),
            'backtrace' => $this->backtrace,
        ];
    }

    /**
     * String representation.
     *
     * @return false|string
     */
    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }
}