<?php


namespace LogEngine;


use LogEngine\Contracts\TransportInterface;
use LogEngine\Models\Span;
use LogEngine\Models\Transaction;
use LogEngine\Transport\AsyncTransport;
use LogEngine\Transport\CurlTransport;
use LogEngine\Transport\TransportConfiguration;

class ApmAgent
{
    /**
     * Transport strategy instance.
     *
     * @var TransportInterface
     */
    protected $transport;

    /**
     * Current transaction.
     *
     * @var Transaction
     */
    protected $transaction;

    /**
     * Logger constructor.
     *
     * @param TransportConfiguration $configuration
     * @throws Exceptions\LogEngineApmException
     */
    public function __construct(TransportConfiguration $configuration)
    {
        switch (getenv('LOGENGINE_TRANSPORT')){
            case 'async':
                $this->transport = new AsyncTransport($configuration);
                break;
            default:
                $this->transport = new CurlTransport($configuration);
        }

        register_shutdown_function(array($this, 'flush'));
    }

    public function startTransaction()
    {
        $transaction = (new Transaction())->start();
        $this->transport->addEntry($transaction);
        return $transaction;
    }

    public function currentTransaction()
    {
        return $this->transaction;
    }

    /**
     * Add new span to the queue.
     *
     * @param $type
     * @return Span
     */
    public function startSpan($type)
    {
        $span = (new Span($type, $this->transaction))->start();
        $this->transport->addEntry($span);
        return $span;
    }

    /**
     * Flush all messages queue programmatically.
     * @throws \Exception
     */
    public function flush()
    {
        $this->transaction->end();
        $this->transport->flush();
    }
}