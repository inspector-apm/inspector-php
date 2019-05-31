<?php


namespace Inspector;


use Inspector\Contracts\TransportInterface;
use Inspector\Models\Error;
use Inspector\Models\Span;
use Inspector\Models\Transaction;
use Inspector\Transport\AsyncTransport;
use Inspector\Transport\CurlTransport;

class Inspector
{
    /**
     * Agent configuration.
     *
     * @var Configuration
     */
    protected $configuration;

    /**
     * Transport strategy.
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
     * @param Configuration $configuration
     * @throws Exceptions\LogEngineApmException
     */
    public function __construct(Configuration $configuration)
    {
        switch (getenv('INSPECTOR_TRANSPORT')) {
            case 'async':
                $this->transport = new AsyncTransport($configuration);
                break;
            default:
                $this->transport = new CurlTransport($configuration);
        }

        $this->configuration = $configuration;
        register_shutdown_function(array($this, 'flush'));
    }

    /**
     * Create and start new Transaction.
     *
     * @param string $name
     * @return Transaction
     * @throws \Exception
     */
    public function startTransaction($name)
    {
        $this->transaction = new Transaction($name);
        $this->transaction->start();
        $this->transport->addEntry($this->transaction);
        return $this->transaction;
    }

    /**
     * Get current transaction instance.
     *
     * @return Transaction
     */
    public function currentTransaction()
    {
        return $this->transaction;
    }

    /**
     * Check if a transaction was just started.
     *
     * @return bool
     */
    public function hasTransaction(): bool
    {
        return isset($this->transaction);
    }

    /**
     * Add new span to the queue.
     *
     * @param $type
     * @return Span
     */
    public function startSpan($type)
    {
        $span = new Span($type, $this->transaction);
        $span->start();
        $this->transport->addEntry($span);
        return $span;
    }

    /**
     * Error reporting.
     *
     * @param \Throwable $exception
     * @return Error
     */
    public function reportException(\Throwable $exception)
    {
        if (!$exception instanceof \Exception && !$exception instanceof \Throwable) {
            throw new \InvalidArgumentException('$exception need to be an instance of Exception or Throwable.');
        }

        $error = new Error($exception, $this->transaction);
        $error->start();
        $this->transport->addEntry($error);
        $error->end();

        return $error;
    }

    /**
     * Flush data to the remote platform.
     *
     * @throws \Exception
     */
    public function flush()
    {
        if (!$this->configuration->isEnabled()) {
            return;
        }

        if (isset($this->transaction)) {
            $this->transaction->end();
            $this->transport->flush();
            unset($this->transaction);
        }
    }
}