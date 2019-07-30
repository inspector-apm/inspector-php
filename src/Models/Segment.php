<?php


namespace Inspector\Models;


use Inspector\Models\Context\SpanContext;

class Segment extends AbstractModel
{
    const MODEL_NAME = 'segment';

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
     * Notes about span.
     *
     * @var string
     */
    protected $label;

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
    public function __construct(Transaction $transaction, $type = 'process')
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

    public function addContext($key, $value): Segment
    {
        $this->context->addCustom($key, $value);
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel($value): AbstractModel
    {
        $this->label = $value;
        return $this;
    }

    public function start($time = null): AbstractModel
    {
        $this->start = round((microtime(true) - $this->transaction->getTimestamp())*1000, 2);
        return parent::start($time);
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
            'hostname' => gethostname(),
            'label' => $this->label,
            'message' => $this->label,
            'timestamp' => $this->timestamp,
            'start' => $this->start,
            'duration' => $this->duration,
            'transaction' => $this->transaction->getHash(),
            'transaction_name' => $this->transaction->getName(),
            'context' => $this->context->jsonSerialize(),
        ];
    }
}
