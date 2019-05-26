<?php


namespace LogEngine\Models;


use LogEngine\Models\Context\SpanContext;

class Span extends AbstractModel
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
     * Time interval relative to transaction timestamp.
     *
     * @var float
     */
    protected $start;

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

    public function getContext(): SpanContext
    {
        return $this->context;
    }

    /**
     * @return AbstractModel
     */
    public function start(): AbstractModel
    {
        $this->start = round((microtime(true) - $this->transaction->getTimestamp())*1000, 2);
        return parent::start();
    }

    /**
     * Array representation.
     *
     * @return array
     */
    public function toArray(): array
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
}