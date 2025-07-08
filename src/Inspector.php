<?php

namespace Inspector;

use Inspector\Exceptions\InspectorException;
use Inspector\Models\Model;
use Inspector\Transports\AsyncTransport;
use Inspector\Transports\TransportInterface;
use Inspector\Models\Error;
use Inspector\Models\Segment;
use Inspector\Models\Transaction;
use Inspector\Transports\CurlTransport;

class Inspector
{
    /**
     * Agent configuration.
     */
    protected Configuration $configuration;

    /**
     * Transport strategy.
     *
     * @var TransportInterface
     */
    protected TransportInterface $transport;

    /**
     * Current transaction.
     */
    protected ?Transaction $transaction = null;

    /**
     * Stack of currently open segments for managing parent-child relationships.
     * The last element is the most recent open segment.
     *
     * @var Segment[]
     */
    protected array $openSegments = [];

    /**
     * Run a list of callbacks before flushing data to the remote platform.
     *
     * @var callable[]
     */
    protected static array $beforeCallbacks = [];

    /**
     * Create an Inspector instance with a single ingestion key.
     */
    public static function create(string $ingestionKey, ?callable $configure = null): static
    {
        $configuration = new Configuration($ingestionKey);

        if ($configure) {
            $configure($configuration);
        }

        return new static($configuration);
    }

    /**
     * Inspector constructor.
     *
     * @param Configuration $configuration
     * @throws Exceptions\InspectorException
     */
    final public function __construct(Configuration $configuration)
    {
        $this->transport = match ($configuration->getTransport()) {
            'async' => new AsyncTransport($configuration),
            default => new CurlTransport($configuration),
        };

        $this->configuration = $configuration;
        \register_shutdown_function(array($this, 'flush'));
    }

    /**
     * Change the configuration instance.
     */
    public function configure(callable $callback): Inspector
    {
        $callback($this->configuration, $this);

        return $this;
    }

    /**
     * Set custom transport.
     *
     * @throws InspectorException
     */
    public function setTransport(TransportInterface|callable $resolver): Inspector
    {
        if (\is_callable($resolver)) {
            $this->transport = $resolver($this->configuration);
        } else {
            $this->transport = $resolver;
        }

        return $this;
    }

    /**
     * Create and start new Transaction.
     *
     * @throws \Exception
     */
    public function startTransaction(string $name): Transaction
    {
        $this->transaction = new Transaction($name);
        $this->transaction->start();

        // Clear any open segments from the previous transaction
        $this->openSegments = [];

        $this->addEntries($this->transaction);
        return $this->transaction;
    }

    /**
     * Get current transaction instance.
     *
     * @deprecated
     * @return null|Transaction
     */
    public function currentTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    /**
     * Get current transaction instance.
     *
     * @return null|Transaction
     */
    public function transaction(): ?Transaction
    {
        return $this->transaction;
    }

    /**
     * Determine if an active transaction exists.
     *
     * @return bool
     */
    public function hasTransaction(): bool
    {
        return isset($this->transaction);
    }

    /**
     * Determine if the current cycle hasn't started its transaction yet.
     *
     * @return bool
     */
    public function needTransaction(): bool
    {
        return $this->isRecording() && !$this->hasTransaction();
    }

    /**
     * Determine if a new segment can be added.
     *
     * @return bool
     */
    public function canAddSegments(): bool
    {
        return $this->isRecording() && $this->hasTransaction();
    }

    /**
     * Check if the monitoring is enabled.
     *
     * @return bool
     */
    public function isRecording(): bool
    {
        return $this->configuration->isEnabled();
    }

    /**
     * Enable recording.
     */
    public function startRecording(): Inspector
    {
        $this->configuration->setEnabled(true);
        return $this;
    }

    /**
     * Stop recording.
     */
    public function stopRecording(): Inspector
    {
        $this->configuration->setEnabled(false);
        return $this;
    }

    /**
     * Get the currently open parent segment, if any.
     */
    protected function getCurrentParentSegment(): ?Segment
    {
        return $this->openSegments === [] ? null : \end($this->openSegments);
    }

    /**
     * Add a new segment to the queue.
     */
    public function startSegment(string $type, ?string $label = null): Segment
    {
        $segment = new Segment($this->transaction, \addslashes($type), $label);

        // Set Inspector reference for lifecycle management
        $segment->setInspector($this);

        // Set a parent relationship if there's an open segment
        $parentSegment = $this->getCurrentParentSegment();
        if ($parentSegment) {
            $segment->setParent($parentSegment->getHash());
        }

        $segment->start();

        // Add to open segments stack
        $this->openSegments[] = $segment;

        $this->addEntries($segment);
        return $segment;
    }

    /**
     * Monitor the execution of a code block.
     *
     * @throws \Throwable
     */
    public function addSegment(callable $callback, string $type, ?string $label = null, bool $throw = true): mixed
    {
        if (!$this->hasTransaction()) {
            return $callback();
        }

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
        return null;
    }

    /**
     * Called by Segment when it ends to remove from open segments stack.
     * This maintains the parent-child relationship hierarchy.
     */
    public function endSegment(Segment $segment): void
    {
        // Remove the segment from the open segments stack
        foreach ($this->openSegments as $index => $openSegment) {
            if ($openSegment === $segment) {
                unset($this->openSegments[$index]);
                // Re-index array to maintain proper stack behavior
                $this->openSegments = \array_values($this->openSegments);
                break;
            }
        }
    }

    /**
     * Error reporting.
     *
     * @throws \Exception
     */
    public function reportException(\Throwable $exception, bool $handled = true): Error
    {
        if (!$this->hasTransaction()) {
            $this->startTransaction(\get_class($exception))->setType('error');
        }

        $segment = $this->startSegment('exception', $exception->getMessage());

        $error = (new Error($exception, $this->transaction))
            ->setHandled($handled);

        $this->addEntries($error);

        $segment->addContext('Error', $error);
        $segment->end();

        return $error;
    }

    /**
     * Add an entry to the queue.
     */
    public function addEntries(array|Model $entries): Inspector
    {
        if ($this->isRecording()) {
            $entries = \is_array($entries) ? $entries : [$entries];
            foreach ($entries as $entry) {
                $this->transport->addEntry($entry);
            }
        }
        return $this;
    }

    /**
     * Define a callback to run before flushing data to the remote platform.
     */
    public static function beforeFlush(callable $callback): void
    {
        static::$beforeCallbacks[] = $callback;
    }

    /**
     * Flush data to the remote platform.
     *
     * @throws \Exception
     */
    public function flush(): void
    {
        if (!$this->isRecording() || !$this->hasTransaction()) {
            $this->reset();
            return;
        }

        if (!$this->transaction->isEnded()) {
            $this->transaction->end();
        }

        foreach (static::$beforeCallbacks as $callback) {
            if (\call_user_func($callback, $this) === false) {
                $this->reset();
                return;
            }
        }

        $this->transport->flush();
        unset($this->transaction);

        // Clear open segments when flushing
        $this->openSegments = [];
    }

    /**
     * Cancel the current transaction, segments, and errors.
     */
    public function reset(): Inspector
    {
        $this->transport->resetQueue();
        unset($this->transaction);
        $this->openSegments = [];
        return $this;
    }

    /**
     * Get information about currently open segments (useful for debugging).
     * Returns an array of segment types and labels.
     */
    public function getOpenSegments(): array
    {
        return \array_map(function (Segment $segment) {
            return [
                'type' => $segment->type,
                'label' => $segment->label,
                'hash' => $segment->getHash()
            ];
        }, $this->openSegments);
    }
}
