<?php


namespace Inspector\Models;


use Inspector\Models\Context\SpanContext;

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
     * Segmenting span types.
     *
     * @var string
     */
    protected $type;

    /**
     * Details about span.
     *
     * @var string
     */
    protected $description;

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription($value): AbstractModel
    {
        $this->description = $value;
        return $this;
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
            'type' => $this->type,
            'description' => $this->description,
            'timestamp' => $this->timestamp,
            'start' => $this->start,
            'duration' => $this->duration,
            'transaction' => $this->transaction->getHash(),
            'context' => $this->context->jsonSerialize(),
        ];
    }
}