<?php


namespace Inspector;


use Inspector\Exceptions\InspectorException;
use Inspector\Models\Arrayable;
use Inspector\Transports\AsyncTransport;
use Inspector\Transports\TransportInterface;
use Inspector\Models\PerformanceModel;
use Inspector\Models\Error;
use Inspector\Models\Segment;
use Inspector\Models\Transaction;
use Inspector\Transports\CurlTransport;

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
     * @throws Exceptions\InspectorException
     */
    public function __construct(Configuration $configuration)
    {
        switch ($configuration->getTransport()) {
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
     * Set custom transport.
     *
     * @param TransportInterface|callable $transport
     * @return $this
     * @throws InspectorException
     */
    public function setTransport($resolver)
    {
        if (is_callable($resolver)) {
            $this->transport = $resolver($this->configuration);
        } elseif ($resolver instanceof TransportInterface) {
            $this->transport = $resolver;
        } else {
            throw new InspectorException('Invalid transport resolver');
        }

        return $this;
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
        $this->transaction = new Transaction(trim($name, '\\'));
        $this->transaction->start();
        $this->addEntries($this->transaction);
        return $this->transaction;
    }

    /**
     * Get current transaction instance.
     *
     * @return null|Transaction
     */
    public function currentTransaction()
    {
        return $this->transaction;
    }

    /**
     * Check if a transaction was started.
     *
     * @return bool
     */
    public function isRecording(): bool
    {
        return isset($this->transaction);
    }

    /**
     * Add new span to the queue.
     *
     * @param string $type
     * @param null|string $label
     * @return PerformanceModel
     */
    public function startSegment($type, $label = null)
    {
        $segment = new Segment($this->transaction, trim($type, '\\'), $label);
        $segment->start();

        $this->addEntries($segment);
        return $segment;
    }

    /**
     * Monitor the execution of a code block.
     *
     * @param $callback
     * @param string $type
     * @param null|string $label
     * @param bool $throw
     * @return mixed|void
     * @throws \Throwable
     */
    public function addSegment($callback, $type, $label = null, $throw = false)
    {
        $segment = $this->startSegment($type, $label);

        try {
            return $callback($segment);
        } catch (\Throwable $exception) {
            if ($throw === true) {
                throw $exception;
            }

            $this->reportException($exception);
        } finally {
            $segment->end();
        }
    }

    /**
     * Error reporting.
     *
     * @param \Throwable $exception
     * @param bool $handled
     * @return Error
     * @throws \Exception
     */
    public function reportException(\Throwable $exception, $handled = true)
    {
        if (!$exception instanceof \Exception && !$exception instanceof \Throwable) {
            throw new \InvalidArgumentException('$exception need to be an instance of Exception or Throwable.');
        }

        if (!$this->isRecording()) {
            $this->startTransaction($exception->getMessage());
        }

        $segment = $this->startSegment('exception', substr($exception->getMessage(), 0, 50));

        $error = (new Error($exception, $this->transaction))
            ->setHandled($handled);

        $this->addEntries($error);

        $segment->addContext('Error', $error)->end();

        return $error;
    }

    /**
     * Add an entry to the queue.
     *
     * @param Arrayable[]|Arrayable $entries
     * @return Inspector
     */
    public function addEntries($entries)
    {
        $entries = is_array($entries) ? $entries : [$entries];
        foreach ($entries as $entry) {
            $this->transport->addEntry($entry);
        }
        return $this;
    }

    /**
     * Flush data to the remote platform.
     *
     * @throws \Exception
     */
    public function flush()
    {
        if (!$this->configuration->isEnabled() || !$this->isRecording()) {
            return;
        }

        if (!$this->transaction->isEnded()) {
            $this->transaction->end();
        }

        $this->transport->flush();
        unset($this->transaction);
    }
}
