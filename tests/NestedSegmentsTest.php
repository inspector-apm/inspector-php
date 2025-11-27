<?php

declare(strict_types=1);

namespace Inspector\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Inspector\Inspector;
use Inspector\Configuration;
use Inspector\Models\Transaction;
use Inspector\Models\Segment;
use Inspector\Transports\TransportInterface;
use Inspector\Models\Error;
use Exception;

use function array_column;
use function array_unique;
use function count;

class NestedSegmentsTest extends TestCase
{
    private Inspector $inspector;
    private MockObject $mockTransport;
    private MockObject $mockConfiguration;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the configuration
        $this->mockConfiguration = $this->createMock(Configuration::class);
        $this->mockConfiguration->method('isEnabled')->willReturn(true);
        $this->mockConfiguration->method('getTransport')->willReturn('sync');

        // Mock the transport
        $this->mockTransport = $this->createMock(TransportInterface::class);

        // Create an inspector instance with mocked dependencies
        $this->inspector = (new Inspector($this->mockConfiguration))->setTransport($this->mockTransport);
    }

    public function testSegmentWithoutParent(): void
    {
        // Test backward compatibility - segments without parents should work as before
        $this->inspector->startTransaction('test-transaction');
        $segment = $this->inspector->startSegment('database', 'select-users');

        $this->assertNull($segment->parent_hash, 'Root segment should not have a parent');
        $this->assertEquals('database', $segment->type);
        $this->assertEquals('select-users', $segment->label);
    }

    public function testSegmentWithParent(): void
    {
        $this->inspector->startTransaction('test-transaction');

        $parentSegment = $this->inspector->startSegment('view', 'user-profile');
        $childSegment = $this->inspector->startSegment('database', 'fetch-user');
        $child2Segment = $this->inspector->startSegment('workflow', 'run-workflow')->end();
        $child3Segment = $this->inspector->startSegment('file', 'read-file')->end();

        $this->assertNull($parentSegment->parent_hash, 'Parent segment should not have a parent');
        $this->assertEquals($parentSegment->getHash(), $childSegment->parent_hash, 'Child segment should have parent hash set');
        $this->assertNotEquals($parentSegment->getHash(), $childSegment->getHash(), 'Parent and child should have different hashes');

        $this->assertEquals($childSegment->getHash(), $child2Segment->parent_hash, 'Child segment should have parent hash set');
        $this->assertEquals($childSegment->getHash(), $child3Segment->parent_hash, 'Child segment should have parent hash set');
    }

    public function testMultipleLevelsOfNesting(): void
    {
        $this->inspector->startTransaction('test-transaction');

        $level1 = $this->inspector->startSegment('controller', 'user-controller');
        $level2 = $this->inspector->startSegment('service', 'user-service');
        $level3 = $this->inspector->startSegment('repository', 'user-repository');
        $level4 = $this->inspector->startSegment('database', 'select-query');

        // Verify the hierarchy
        $this->assertNull($level1->parent_hash);
        $this->assertEquals($level1->getHash(), $level2->parent_hash);
        $this->assertEquals($level2->getHash(), $level3->parent_hash);
        $this->assertEquals($level3->getHash(), $level4->parent_hash);

        // Verify all hashes are unique
        $hashes = [$level1->getHash(), $level2->getHash(), $level3->getHash(), $level4->getHash()];
        $this->assertEquals(4, count(array_unique($hashes)), 'All segment hashes should be unique');
    }

    public function testOpenSegmentsStack(): void
    {
        $this->inspector->startTransaction('test-transaction');

        // Initially no open segments
        $this->assertEmpty($this->inspector->getOpenSegments());

        $this->inspector->startSegment('type1', 'label1');
        $openSegments = $this->inspector->getOpenSegments();
        $this->assertCount(1, $openSegments);
        /** @phpstan-ignore offsetAccess.notFound */
        $this->assertEquals('type1', $openSegments[0]['type']);
        /** @phpstan-ignore offsetAccess.notFound */
        $this->assertEquals('label1', $openSegments[0]['label']);

        $this->inspector->startSegment('type2', 'label2');
        $openSegments = $this->inspector->getOpenSegments();
        $this->assertCount(2, $openSegments);
        $this->assertEquals('type2', $openSegments[1]['type']); // Most recent is last

        $this->inspector->startSegment('type3', 'label3');
        $openSegments = $this->inspector->getOpenSegments();
        $this->assertCount(3, $openSegments);
        $this->assertEquals('type3', $openSegments[2]['type']); // Most recent is last
    }

    public function testSegmentEndingRemovesFromStack(): void
    {
        $this->inspector->startTransaction('test-transaction');

        $segment1 = $this->inspector->startSegment('type1', 'label1');
        $segment2 = $this->inspector->startSegment('type2', 'label2');
        $segment3 = $this->inspector->startSegment('type3', 'label3');

        $this->assertCount(3, $this->inspector->getOpenSegments());

        // End the most recent segment (LIFO)
        $segment3->end();
        $openSegments = $this->inspector->getOpenSegments();
        $this->assertCount(2, $openSegments);
        $this->assertEquals('type2', $openSegments[1]['type']); // segment2 is now the most recent

        $segment2->end();
        $openSegments = $this->inspector->getOpenSegments();
        $this->assertCount(1, $openSegments);
        $this->assertEquals('type1', $openSegments[0]['type']);

        $segment1->end();
        $this->assertEmpty($this->inspector->getOpenSegments());
    }

    public function testSegmentEndingOutOfOrder(): void
    {
        $this->inspector->startTransaction('test-transaction');

        $this->inspector->startSegment('type1', 'label1');
        $segment2 = $this->inspector->startSegment('type2', 'label2');
        $this->inspector->startSegment('type3', 'label3');

        // End segment2 (middle one) first
        $segment2->end();

        $openSegments = $this->inspector->getOpenSegments();
        $this->assertCount(2, $openSegments);

        // Verify that segment1 and segment3 are still in the stack
        $types = array_column($openSegments, 'type');
        $this->assertContains('type1', $types);
        $this->assertContains('type3', $types);
        $this->assertNotContains('type2', $types);
    }

    public function testTransactionBoundaryClearsStack(): void
    {
        // Start first transaction with segments
        $this->inspector->startTransaction('transaction-1');
        $this->inspector->startSegment('type1', 'label1');
        $this->inspector->startSegment('type2', 'label2');

        $this->assertCount(2, $this->inspector->getOpenSegments());

        // Start new transaction should clear the stack
        $this->inspector->startTransaction('transaction-2');
        $this->assertEmpty($this->inspector->getOpenSegments());

        // New segments should work in new transaction
        $newSegment = $this->inspector->startSegment('type3', 'label3');
        $this->assertNull($newSegment->parent_hash, 'New segment should not have parent from previous transaction');
        $this->assertCount(1, $this->inspector->getOpenSegments());
    }

    public function testMixedStartAndAddSegment(): void
    {
        $this->inspector->startTransaction('test-transaction');

        $this->inspector->startSegment('parent', 'parent-operation');

        $result = $this->inspector->addSegment(function ($segment): string {
            $this->assertEquals($this->inspector->getOpenSegments()[0]['hash'], $segment->parent_hash);

            // Start another segment inside the callback
            $nestedSegment = $this->inspector->startSegment('nested', 'nested-operation');
            $this->assertEquals($segment->getHash(), $nestedSegment->parent_hash);
            $nestedSegment->end();

            return 'test-result';
        }, 'child', 'child-operation');

        $this->assertEquals('test-result', $result);

        // After addSegment completes, only parent should remain open
        $openSegments = $this->inspector->getOpenSegments();
        $this->assertCount(1, $openSegments);
        $this->assertEquals('parent', $openSegments[0]['type']);
    }

    public function testSegmentHashGeneration(): void
    {
        $this->inspector->startTransaction('test-transaction');

        $segment1 = $this->inspector->startSegment('database', 'query1');
        $segment2 = $this->inspector->startSegment('database', 'query2');
        $segment3 = $this->inspector->startSegment('database', 'query1'); // Same type and label

        // All hashes should be unique even with same type/label
        $this->assertNotEquals($segment1->getHash(), $segment2->getHash());
        $this->assertNotEquals($segment1->getHash(), $segment3->getHash());
        $this->assertNotEquals($segment2->getHash(), $segment3->getHash());

        // Hashes should be non-empty strings
        $this->assertNotEmpty($segment1->getHash());
        $this->assertIsString($segment1->getHash());
    }

    public function testFlushClearsOpenSegments(): void
    {
        $this->inspector->startTransaction('test-transaction');
        $this->inspector->startSegment('type1', 'label1');
        $this->inspector->startSegment('type2', 'label2');

        $this->assertCount(2, $this->inspector->getOpenSegments());

        $this->inspector->flush();

        $this->assertEmpty($this->inspector->getOpenSegments());
    }

    public function testResetClearsOpenSegments(): void
    {
        $this->inspector->startTransaction('test-transaction');
        $this->inspector->startSegment('type1', 'label1');
        $this->inspector->startSegment('type2', 'label2');

        $this->assertCount(2, $this->inspector->getOpenSegments());

        $this->inspector->reset();

        $this->assertEmpty($this->inspector->getOpenSegments());
    }

    public function testExceptionReportingWithNestedSegments(): void
    {
        $this->inspector->startTransaction('test-transaction');

        $this->inspector->startSegment('controller', 'user-action');

        $exception = new Exception('Test exception');
        $error = $this->inspector->reportException($exception);

        // Exception reporting should create its own segment hierarchy
        $openSegments = $this->inspector->getOpenSegments();
        $this->assertCount(1, $openSegments); // Only parent should remain, exception segment auto-ends
        $this->assertEquals('controller', $openSegments[0]['type']);

        $this->assertInstanceOf(Error::class, $error);
        $this->assertEquals('Test exception', $error->message);
    }

    public function testAddSegmentWithExceptionHandling(): void
    {
        $this->inspector->startTransaction('test-transaction');

        $this->inspector->startSegment('parent', 'parent-operation');

        // Test with throw = false
        $result = $this->inspector->addSegment(function ($segment): void {
            throw new Exception('Test exception');
        }, 'child', 'child-operation', false);

        $this->assertNull($result);

        // Parent segment should still be open
        $openSegments = $this->inspector->getOpenSegments();
        $this->assertCount(1, $openSegments);
        $this->assertEquals('parent', $openSegments[0]['type']);
    }

    public function testParentChildRelationshipConsistency(): void
    {
        $this->inspector->startTransaction('test-transaction');

        $segments = [];

        // Create a complex nested structure
        $segments[] = $this->inspector->startSegment('level1', 'operation1');
        $segments[] = $this->inspector->startSegment('level2', 'operation2');
        $segments[] = $this->inspector->startSegment('level3', 'operation3');

        // End middle segment
        $segments[1]->end();

        // Start new segment - should be child of level1 (since level2 ended)
        $segments[] = $this->inspector->startSegment('level2b', 'operation2b');

        // Verify relationships
        $this->assertNull($segments[0]->parent_hash); // level1 has no parent
        $this->assertEquals($segments[0]->getHash(), $segments[1]->parent_hash); // level2 child of level1
        $this->assertEquals($segments[1]->getHash(), $segments[2]->parent_hash); // level3 child of level2
        $this->assertEquals($segments[2]->getHash(), $segments[3]->parent_hash); // level2b child of level3 (current open)
    }

    public function testSegmentWithoutTransaction(): void
    {
        // Test backward compatibility - should handle gracefully when no transaction exists
        $this->mockConfiguration->method('isEnabled')->willReturn(false);

        $this->assertFalse($this->inspector->hasTransaction());
        $this->assertEmpty($this->inspector->getOpenSegments());

        // This should not throw an exception but also not create segments
        $this->inspector->addSegment(fn(): string => 'result', 'test', 'test-operation');
    }

    public function testGetOpenSegmentsFormat(): void
    {
        $this->inspector->startTransaction('test-transaction');

        $segment = $this->inspector->startSegment('database', 'user-query');
        $openSegments = $this->inspector->getOpenSegments();

        $this->assertCount(1, $openSegments);
        $this->assertArrayHasKey('type', $openSegments[0]);
        $this->assertArrayHasKey('label', $openSegments[0]);
        $this->assertArrayHasKey('hash', $openSegments[0]);

        $this->assertEquals('database', $openSegments[0]['type']);
        $this->assertEquals('user-query', $openSegments[0]['label']);
        $this->assertEquals($segment->getHash(), $openSegments[0]['hash']);
    }
}
