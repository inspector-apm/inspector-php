<?php

declare(strict_types=1);

namespace Inspector;

use Inspector\Exceptions\InspectorException;
use Inspector\Models\Model;
use Inspector\Transports\AsyncTransport;
use Inspector\Transports\TransportInterface;
use Inspector\Models\Error;
use Inspector\Models\Segment;
use Inspector\Models\Transaction;
use Inspector\Transports\CurlTransport;
use Exception;
use Throwable;

use function call_user_func;
use function is_array;
use function is_callable;
use function register_shutdown_function;
use function is_null;

class Inspector extends Scope
{
    /**
     * Agent configuration.
     */
    protected Configuration $configuration;

    /**
     * Transport strategy.
     */
    protected TransportInterface $transport;

    /**
     * Current transaction.
     */
    protected ?Transaction $transaction = null;

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
     * @throws Exceptions\InspectorException
     */
    final public function __construct(Configuration $configuration)
    {
        parent::__construct(null, null);

        $this->transport = match ($configuration->getTransport()) {
            'async' => new AsyncTransport($configuration),
            default => new CurlTransport($configuration),
        };

        $this->configuration = $configuration;
        register_shutdown_function([$this, 'flush']);
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
        $this->transport = is_callable($resolver) ? $resolver($this->configuration) : $resolver;

        return $this;
    }

    /**
     * Create and start new Transaction.
     *
     * @throws Exception
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
     */
    public function currentTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    /**
     * Get current transaction instance.
     */
    public function transaction(): ?Transaction
    {
        return $this->transaction;
    }

    /**
     * Determine if an active transaction exists.
     */
    public function hasTransaction(): bool
    {
        return !is_null($this->transaction);
    }

    /**
     * Determine if the current cycle hasn't started its transaction yet.
     */
    public function needTransaction(): bool
    {
        return $this->isRecording() && !$this->hasTransaction();
    }

    /**
     * Determine if a new segment can be added.
     */
    public function canAddSegments(): bool
    {
        return $this->isRecording() && $this->hasTransaction();
    }

    /**
     * Check if the monitoring is enabled.
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
     * Inspector resolves to itself.
     */
    protected function resolveInspector(): Inspector
    {
        return $this;
    }

    /**
     * Fork the current context into an independent Scope.
     *
     * @throws InspectorException
     */
    public function fork(): Scope
    {
        if (!$this->hasTransaction()) {
            throw new InspectorException('Cannot fork without an active transaction.');
        }

        return new Scope($this, $this->resolveParentHash());
    }

    /**
     * Error reporting.
     *
     * @throws Exception
     */
    public function reportException(Throwable $exception, bool $handled = true): Error
    {
        if (!$this->hasTransaction()) {
            $this->startTransaction($exception::class)->setType('error');
        }

        $segment = $this->startSegment('exception', $exception->getMessage());

        $error = (new Error($exception, $this->transaction))
            ->setHandled($handled);

        $this->addEntries($error);

        $segment->addContext('Error', [
            'message' => $error->message,
            'class' => $error->class,
            'file' => $error->file,
            'line' => $error->line,
        ]);
        $segment->end();

        return $error;
    }

    public function registerGlobalHandler(): GlobalExceptionHandler
    {
        return new GlobalExceptionHandler($this);
    }

    /**
     * Add an entry to the queue.
     *
     * @param Model|Model[] $entries
     */
    public function addEntries(array|Model $entries): Inspector
    {
        if ($this->isRecording()) {
            $entries = is_array($entries) ? $entries : [$entries];
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
     * @throws Exception
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
            if (call_user_func($callback, $this) === false) {
                $this->reset();
                return;
            }
        }

        $this->transport->flush();
        $this->transaction = null;

        // Clear open segments when flushing
        $this->openSegments = [];
    }

    /**
     * Cancel the current transaction, segments, and errors.
     */
    public function reset(): Inspector
    {
        $this->transport->resetQueue();
        $this->transaction = null;
        $this->openSegments = [];
        return $this;
    }
}
