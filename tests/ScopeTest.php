<?php

declare(strict_types=1);

namespace Inspector\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Inspector\Configuration;
use Inspector\Inspector;
use Inspector\Scope;
use Inspector\Models\Segment;
use Inspector\Models\Transaction;
use Inspector\Transports\TransportInterface;
use Exception;

class ScopeTest extends TestCase
{
    private Inspector $inspector;
    private MockObject $mockTransport;
    private MockObject $mockConfiguration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfiguration = $this->createMock(Configuration::class);
        $this->mockConfiguration->method('isEnabled')->willReturn(true);
        $this->mockConfiguration->method('getTransport')->willReturn('sync');

        $this->mockTransport = $this->createMock(TransportInterface::class);

        $this->inspector = (new Inspector($this->mockConfiguration))->setTransport($this->mockTransport);
    }

    public function testForkCreatesScope(): void
    {
        $this->inspector->startTransaction('test-transaction');
        $parent = $this->inspector->startSegment('controller', 'user-action');

        $scope = $this->inspector->fork();

        $this->assertEquals($parent->getHash(), $scope->getBaseParentHash());
    }

    public function testForkWithNoOpenSegments(): void
    {
        $this->inspector->startTransaction('test-transaction');

        $scope = $this->inspector->fork();

        $this->assertNull($scope->getBaseParentHash());

        // Segments created in this scope have no parent
        $segment = $scope->startSegment('http', 'api-call');
        $this->assertNull($segment->parent_hash);
    }

    public function testForkWithoutTransactionThrowsException(): void
    {
        $this->expectException(\Inspector\Exceptions\InspectorException::class);
        $this->expectExceptionMessage('Cannot fork without an active transaction.');

        $this->inspector->fork();
    }

    public function testScopeSegmentHasCorrectParent(): void
    {
        $this->inspector->startTransaction('test-transaction');
        $parent = $this->inspector->startSegment('controller', 'user-action');

        $scope = $this->inspector->fork();
        $child = $scope->startSegment('http', 'api-call');

        $this->assertEquals($parent->getHash(), $child->parent_hash);
    }

    public function testScopeSegmentsAreIsolatedFromMainStack(): void
    {
        $this->inspector->startTransaction('test-transaction');
        $segmentA = $this->inspector->startSegment('controller', 'action');

        $scope = $this->inspector->fork();

        $segmentB = $this->inspector->startSegment('database', 'query');
        $segmentC = $scope->startSegment('http', 'api-call');

        // segmentB is on the Inspector stack — parent is segmentA
        $this->assertEquals($segmentA->getHash(), $segmentB->parent_hash);

        // segmentC is on the Scope stack — parent is also segmentA (base parent)
        $this->assertEquals($segmentA->getHash(), $segmentC->parent_hash);

        // Inspector stack has A and B, not C
        $this->assertCount(2, $this->inspector->getOpenSegments());

        // Scope stack has only C
        $this->assertCount(1, $scope->getOpenSegments());
    }

    public function testNestedScopes(): void
    {
        $this->inspector->startTransaction('test-transaction');
        $segmentA = $this->inspector->startSegment('controller', 'action');

        $scope1 = $this->inspector->fork();
        $segmentB = $scope1->startSegment('http', 'request-1');

        $scope2 = $scope1->fork();
        $segmentC = $scope2->startSegment('database', 'query');

        // segmentB parent is segmentA (scope1's base parent)
        $this->assertEquals($segmentA->getHash(), $segmentB->parent_hash);

        // segmentC parent is segmentB (scope2's base parent = top of scope1's stack)
        $this->assertEquals($segmentB->getHash(), $segmentC->parent_hash);
    }

    public function testScopeEndSegment(): void
    {
        $this->inspector->startTransaction('test-transaction');
        $this->inspector->startSegment('controller', 'action');

        $scope = $this->inspector->fork();
        $segment1 = $scope->startSegment('http', 'call-1');
        $scope->startSegment('http', 'call-2');

        $this->assertCount(2, $scope->getOpenSegments());

        $segment1->end();

        $openSegments = $scope->getOpenSegments();
        $this->assertCount(1, $openSegments);
        $this->assertEquals('call-2', $openSegments[0]['label']);

        // Inspector stack is unaffected
        $this->assertCount(1, $this->inspector->getOpenSegments());
    }

    public function testScopeAddSegment(): void
    {
        $this->inspector->startTransaction('test-transaction');
        $parent = $this->inspector->startSegment('controller', 'action');

        $scope = $this->inspector->fork();

        $result = $scope->addSegment(function (Segment $segment) use ($parent): string {
            // Segment should have correct parent within the scope
            $this->assertEquals($parent->getHash(), $segment->parent_hash);
            return 'done';
        }, 'http', 'api-call');

        $this->assertEquals('done', $result);

        // Segment should be auto-ended
        $this->assertEmpty($scope->getOpenSegments());
    }

    public function testScopeAddSegmentWithException(): void
    {
        $this->inspector->startTransaction('test-transaction');
        $this->inspector->startSegment('controller', 'action');

        $scope = $this->inspector->fork();

        $result = $scope->addSegment(function (): void {
            throw new Exception('Test exception');
        }, 'http', 'api-call', false);

        $this->assertNull($result);

        // Segment should be auto-ended despite exception
        $this->assertEmpty($scope->getOpenSegments());
    }

    public function testScopeDelegatesToInspectorTransport(): void
    {
        $this->inspector->startTransaction('test-transaction');
        $this->inspector->startSegment('controller', 'action');

        $scope = $this->inspector->fork();

        // Expect the transport to receive the segment entry
        $this->mockTransport->expects($this->atLeastOnce())
            ->method('addEntry');

        $scope->startSegment('http', 'api-call');
    }

    public function testInterleavedScopesProduceCorrectHierarchy(): void
    {
        $this->inspector->startTransaction('test-transaction');
        $controller = $this->inspector->startSegment('controller', 'action');

        $scope1 = $this->inspector->fork();
        $scope2 = $this->inspector->fork();

        // Both scopes capture the controller as base parent
        $this->assertEquals($controller->getHash(), $scope1->getBaseParentHash());
        $this->assertEquals($controller->getHash(), $scope2->getBaseParentHash());

        // Interleave segment creation
        $segB = $scope1->startSegment('http', 'request-A');
        $segC = $scope2->startSegment('http', 'request-B');
        $segD = $scope1->startSegment('database', 'query-A');
        $segE = $scope2->startSegment('database', 'query-B');

        // Both B and C are children of controller (siblings)
        $this->assertEquals($controller->getHash(), $segB->parent_hash);
        $this->assertEquals($controller->getHash(), $segC->parent_hash);

        // D is child of B (within scope1)
        $this->assertEquals($segB->getHash(), $segD->parent_hash);

        // E is child of C (within scope2)
        $this->assertEquals($segC->getHash(), $segE->parent_hash);

        // Without scopes, segE.parent would incorrectly be segD
        $this->assertNotEquals($segD->getHash(), $segE->parent_hash);
    }

    public function testScopeGetOpenSegmentsFormat(): void
    {
        $this->inspector->startTransaction('test-transaction');
        $this->inspector->startSegment('controller', 'action');

        $scope = $this->inspector->fork();
        $segment = $scope->startSegment('http', 'api-call');

        $openSegments = $scope->getOpenSegments();

        $this->assertCount(1, $openSegments);
        $this->assertArrayHasKey('type', $openSegments[0]);
        $this->assertArrayHasKey('label', $openSegments[0]);
        $this->assertArrayHasKey('hash', $openSegments[0]);
        $this->assertEquals('http', $openSegments[0]['type']);
        $this->assertEquals('api-call', $openSegments[0]['label']);
        $this->assertEquals($segment->getHash(), $openSegments[0]['hash']);
    }

    public function testBackwardCompatibilitySetInspector(): void
    {
        $transaction = new Transaction('test');
        $transaction->start();

        $mockInspector = $this->createMock(Inspector::class);
        $mockInspector->expects($this->once())
            ->method('endSegment')
            ->with($this->isInstanceOf(Segment::class));

        $segment = new Segment($transaction, 'database', 'query');
        $segment->setInspector($mockInspector); // Deprecated but still works
        $segment->start();
        $segment->end();
    }

    public function testSetStackManager(): void
    {
        $this->inspector->startTransaction('test-transaction');
        $this->inspector->startSegment('controller', 'action');

        $scope = $this->inspector->fork();

        // Use the Scope as a stack manager directly on a manually created segment
        $transaction = $this->inspector->transaction();
        $segment = new Segment($transaction, 'custom', 'manual');
        $segment->setStackManager($scope);
        $segment->start();

        // Ending should call scope's endSegment without errors
        $segment->end();

        // The manually managed segment is not in scope's stack
        // (we didn't push it via startSegment), but endSegment handles it gracefully
        $this->assertEmpty($scope->getOpenSegments());
    }

    public function testScopeForkWithoutTransactionThrowsException(): void
    {
        $this->expectException(\Inspector\Exceptions\InspectorException::class);

        // Create a scope first, then reset the inspector transaction
        $this->inspector->startTransaction('test-transaction');
        $scope = $this->inspector->fork();
        $this->inspector->reset();

        // Now trying to fork from the scope should fail
        $scope->fork();
    }

    public function testScopeNestingWithinScope(): void
    {
        $this->inspector->startTransaction('test-transaction');
        $root = $this->inspector->startSegment('controller', 'action');

        $scope1 = $this->inspector->fork();
        $segA = $scope1->startSegment('http', 'outer');

        $scope2 = $scope1->fork();
        $segB = $scope2->startSegment('database', 'inner');

        // segA parent is root (scope1's base parent)
        $this->assertEquals($root->getHash(), $segA->parent_hash);

        // segB parent is segA (scope2's base parent = top of scope1's stack)
        $this->assertEquals($segA->getHash(), $segB->parent_hash);

        // Stacks are independent
        $this->assertCount(1, $scope1->getOpenSegments());
        $this->assertCount(1, $scope2->getOpenSegments());
    }

    public function testMultipleForksFromSamePoint(): void
    {
        $this->inspector->startTransaction('test-transaction');
        $controller = $this->inspector->startSegment('controller', 'action');

        // Fork multiple scopes from the same point
        $scopes = [];
        for ($i = 0; $i < 5; $i++) {
            $scopes[] = $this->inspector->fork();
        }

        // Each scope should have the controller as base parent
        foreach ($scopes as $scope) {
            $this->assertEquals($controller->getHash(), $scope->getBaseParentHash());

            $segment = $scope->startSegment('http', "request-{$i}");
            $this->assertEquals($controller->getHash(), $segment->parent_hash);
        }

        // Inspector stack should only have the controller
        $this->assertCount(1, $this->inspector->getOpenSegments());
    }
}
