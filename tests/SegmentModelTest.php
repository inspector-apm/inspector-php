<?php

namespace Inspector\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Inspector\Models\Segment;
use Inspector\Models\Transaction;
use Inspector\Inspector;

class SegmentModelTest extends TestCase
{
    /** @var Transaction */
    private Transaction $mockTransaction;

    /** @var MockObject&Inspector */
    private MockObject $mockInspector;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Transaction
        $this->mockTransaction = new Transaction('test-transaction');
        $this->mockTransaction->hash = 'transaction-hash-123';
        $this->mockTransaction->start();

        // Mock Inspector
        $this->mockInspector = $this->createMock(Inspector::class);
    }

    public function testSegmentConstruction(): void
    {
        $segment = new Segment($this->mockTransaction, 'database', 'select-users');

        $this->assertEquals('database', $segment->type);
        $this->assertEquals('select-users', $segment->label);
        $this->assertEquals('segment', $segment->model);
        $this->assertNull($segment->parent_hash);
    }

    public function testSetParent(): void
    {
        $segment = new Segment($this->mockTransaction, 'database', 'select-users');
        $parentHash = 'parent-hash-123';

        $returnedSegment = $segment->setParent($parentHash);

        $this->assertEquals($parentHash, $segment->parent_hash);
        $this->assertSame($segment, $returnedSegment, 'setParent should return the segment instance for chaining');
    }

    public function testSetParentWithNull(): void
    {
        $segment = new Segment($this->mockTransaction, 'database', 'select-users');

        // Set a parent first
        $segment->setParent('some-parent-hash');
        $this->assertEquals('some-parent-hash', $segment->parent_hash);

        // Now set it to null
        $segment->setParent(null);
        $this->assertNull($segment->parent_hash);
    }

    public function testSetInspector(): void
    {
        $segment = new Segment($this->mockTransaction, 'database', 'select-users');

        $returnedSegment = $segment->setInspector($this->mockInspector);

        $this->assertSame($segment, $returnedSegment, 'setInspector should return the segment instance for chaining');
    }

    public function testHashUniqueness(): void
    {
        $segment1 = new Segment($this->mockTransaction, 'database', 'query1');
        $segment2 = new Segment($this->mockTransaction, 'database', 'query1');
        $segment3 = new Segment($this->mockTransaction, 'cache', 'different-operation');

        // Even with the same type and label, hashes should be different
        $this->assertNotEquals($segment1->getHash(), $segment2->getHash());
        $this->assertNotEquals($segment1->getHash(), $segment3->getHash());
        $this->assertNotEquals($segment2->getHash(), $segment3->getHash());
    }

    public function testHashFormat(): void
    {
        $segment = new Segment($this->mockTransaction, 'database', 'select-users');
        $hash = $segment->getHash();

        // Should be a valid hash format (assuming SHA256 which produces 64 character hex string)
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public function testEndNotifiesInspector(): void
    {
        $segment = new Segment($this->mockTransaction, 'database', 'select-users');
        $segment->setInspector($this->mockInspector);

        // Expect Inspector to be notified when segment ends
        $this->mockInspector->expects($this->once())
            ->method('endSegment')
            ->with($segment);

        $segment->start();
        \usleep(100);
        $segment->end();

        // Verify the segment has a duration after ending
        $this->assertNotNull($segment->duration);
        $this->assertGreaterThan(0, $segment->duration);
    }

    public function testEndWithoutInspector(): void
    {
        $segment = new Segment($this->mockTransaction, 'database', 'select-users');
        // Don't set inspector

        $segment->start();

        // This should not throw an exception even without inspector
        $segment->end();

        $this->assertNotNull($segment->duration);
    }

    public function testSegmentMethodChaining(): void
    {
        $segment = new Segment($this->mockTransaction, 'database', 'select-users');

        // Test method chaining
        $result = $segment
            ->setInspector($this->mockInspector)
            ->setParent('parent-hash')
            ->setColor('blue')
            ->start();

        $this->assertSame($segment, $result);
        $this->assertEquals('parent-hash', $segment->parent_hash);
        $this->assertEquals('blue', $segment->color);
    }

    public function testSegmentTimingCalculation(): void
    {
        $segment = new Segment($this->mockTransaction, 'database', 'select-users');

        $startTime = \microtime(true);
        $segment->start();

        // Simulate some work
        \usleep(1000); // 1ms

        $segment->end();

        $this->assertNotNull($segment->duration);
        $this->assertGreaterThan(0, $segment->duration);
        $this->assertLessThan(100, $segment->duration); // Should be less than 100ms for this test
    }

    public function testSegmentWithSpecialCharacters(): void
    {
        // Test that segments handle special characters in type and label
        $segment = new Segment($this->mockTransaction, 'database/query', 'select * from "users"');

        $this->assertEquals('database/query', $segment->type);
        $this->assertEquals('select * from "users"', $segment->label);
    }

    public function testStartCalculatesRelativeTime(): void
    {
        $transactionTimestamp = \microtime(true);

        $this->mockTransaction->timestamp = $transactionTimestamp;

        $segment = new Segment($this->mockTransaction, 'database', 'select-users');

        $segmentStartTime = \microtime(true);
        $segment->start();

        // The start time should be relative to the transaction timestamp in milliseconds
        $expectedRelativeStart = \round(($segmentStartTime - $transactionTimestamp) * 1000, 2);

        // Allow for small timing differences in test execution
        $this->assertEqualsWithDelta($expectedRelativeStart, $segment->start, 10.0);
    }

    public function testSegmentStartWithCustomTimestamp(): void
    {
        $transactionTimestamp = \microtime(true);
        $customStartTimestamp = $transactionTimestamp + 5;

        $this->mockTransaction->timestamp = $transactionTimestamp;

        $segment = new Segment($this->mockTransaction, 'database', 'select users');
        $segment->start($customStartTimestamp);

        $this->assertEqualsWithDelta(5000, $segment->start, 10.0);
        $this->assertEquals($customStartTimestamp, $segment->timestamp);
    }
}
