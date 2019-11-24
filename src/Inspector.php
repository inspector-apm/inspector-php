<?php


namespace Inspector;


use Inspector\Contracts\TransportInterface;
use Inspector\Models\AbstractModel;
use Inspector\Models\Error;
use Inspector\Models\Segment;
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
    public function hasTransaction(): bool
    {
        return isset($this->transaction);
    }

    /**
     * Check if a transaction was started.
     *
     * @return bool
     */
    public function isRecording(): bool
    {
        return $this->hasTransaction();
    }

    /**
     * Add new span to the queue.
     *
     * @param string $type
     * @param null|string $label
     * @return AbstractModel
     */
    public function startSegment($type, $label = null)
    {
        $segment = (new Segment($this->transaction, $type))->start();
        if($label !== null){
            $segment->setLabel($label);
        }

        $this->transport->addEntry($segment);
        return $segment;
    }

    /**
     * Monitor the execution of the callback.
     *
     * @param $callback
     * @param string $type
     * @param null|string $label
     * @param bool $throw
     * @throws \Throwable
     */
    public function addSegment($callback, $type, $label = null, $throw = false)
    {
        $segment = $this->startSegment($type, $label);

        try {
            $callback();
        } catch (\Throwable $exception) {
            $this->reportException($exception);
            if($throw) {
                throw $exception;
            }
        } finally {
            $segment->end();
        }
    }

    /**
     * Error reporting.
     *
     * @param \Throwable $exception
     * @param bool $handled
     * @return mixed
     */
    public function reportException(\Throwable $exception, $handled = true)
    {
        if (!$exception instanceof \Exception && !$exception instanceof \Throwable) {
            throw new \InvalidArgumentException('$exception need to be an instance of Exception or Throwable.');
        }

        $error = (new Error($exception, $this->transaction))
            ->setHandled($handled)
            ->start();
        $this->transport->addEntry($error);
        $error->end();

        $this->startSegment('exception', substr($exception->getMessage(), 0, 50))
            ->addContext('error', $error)
            ->end($error->getDuration());

        return $error;
    }

    /**
     * Add an entry to the queue.
     *
     * @param array|AbstractModel $entries
     * @return Inspector
     */
    public function addEntries($entries)
    {
        $entries = is_array($entries) ? $entries : [$entries];
        foreach ($entries as $entry){
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

        $this->transaction->end();
        $this->transport->flush();
        unset($this->transaction);
    }
}
