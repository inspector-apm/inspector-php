<?php

declare(strict_types=1);

namespace Inspector;

use Inspector\Exceptions\InspectorException;
use Inspector\Models\Segment;
use Throwable;

use function addslashes;
use function array_map;
use function array_values;
use function end;

class Scope implements SegmentStack
{
    /**
     * This scope is an independent stack of open segments.
     *
     * @var Segment[]
     */
    protected array $openSegments = [];

    /**
     * @param string|null $baseParentHash The parent hash captured at fork time.
     */
    public function __construct(
        /**
         * The Inspector instance that owns the transaction and transport.
         */
        protected Inspector $inspector,
        protected ?string $baseParentHash
    ) {
    }

    /**
     * Resolve the parent hash for a new segment in this scope.
     * Uses the top of this scope's stack, or falls back to the captured base parent.
     */
    protected function resolveParentHash(): ?string
    {
        if ($this->openSegments !== []) {
            return end($this->openSegments)->getHash();
        }

        return $this->baseParentHash;
    }

    /**
     * Create and start a new segment in this scope.
     */
    public function startSegment(string $type, ?string $label = null): Segment
    {
        $transaction = $this->inspector->transaction();

        $segment = new Segment($transaction, addslashes($type), $label);

        // Set this Scope as the stack manager (not the Inspector)
        $segment->setStackManager($this);

        // Set parent: scope stack > base parent > null
        $parentHash = $this->resolveParentHash();
        if ($parentHash !== null) {
            $segment->setParent($parentHash);
        }

        $segment->start();

        $this->openSegments[] = $segment;

        $this->inspector->addEntries($segment);

        return $segment;
    }

    /**
     * Monitor the execution of a code block inside this scope.
     *
     * @throws Throwable
     */
    public function addSegment(callable $callback, string $type, ?string $label = null, bool $throw = true): mixed
    {
        if (!$this->inspector->hasTransaction()) {
            return $callback();
        }

        $segment = $this->startSegment($type, $label);
        try {
            return $callback($segment);
        } catch (Throwable $exception) {
            if ($throw) {
                throw $exception;
            }

            $this->inspector->reportException($exception);
        } finally {
            $segment->end();
        }

        return null;
    }

    /**
     * Remove a segment from this scope's stack when it ends.
     */
    public function endSegment(Segment $segment): void
    {
        foreach ($this->openSegments as $index => $openSegment) {
            if ($openSegment === $segment) {
                unset($this->openSegments[$index]);
                $this->openSegments = array_values($this->openSegments);
                break;
            }
        }
    }

    /**
     * Fork this scope into a new independent scope.
     *
     * @throws InspectorException
     */
    public function fork(): Scope
    {
        if (!$this->inspector->hasTransaction()) {
            throw new InspectorException('Cannot fork without an active transaction.');
        }

        return new Scope($this->inspector, $this->resolveParentHash());
    }

    /**
     * Get information about currently open segments in this scope.
     */
    public function getOpenSegments(): array
    {
        return array_map(fn (Segment $segment): array => [
            'type' => $segment->type,
            'label' => $segment->label,
            'hash' => $segment->getHash(),
        ], $this->openSegments);
    }

    /**
     * Get the base parent hash captured at fork time.
     */
    public function getBaseParentHash(): ?string
    {
        return $this->baseParentHash;
    }
}
