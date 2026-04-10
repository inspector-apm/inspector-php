<?php

declare(strict_types=1);

namespace Inspector;

use Inspector\Models\Segment;
use Throwable;

interface SegmentStack
{
    /**
     * Create, start, and enqueue a new segment.
     * The segment's parent is determined by the implementor's internal stack.
     */
    public function startSegment(string $type, ?string $label = null): Segment;

    /**
     * Monitor the execution of a code block inside a segment that auto-ends.
     *
     * @throws Throwable
     */
    public function addSegment(callable $callback, string $type, ?string $label = null, bool $throw = true): mixed;

    /**
     * Remove a segment from the internal stack when it ends.
     */
    public function endSegment(Segment $segment): void;

    /**
     * Fork the current context into an independent scope.
     *
     * @throws Exceptions\InspectorException
     */
    public function fork(): Scope;
}
