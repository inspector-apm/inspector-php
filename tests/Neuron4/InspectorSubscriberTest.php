<?php

declare(strict_types=1);

namespace Inspector\Tests\Neuron4;

use Inspector\Configuration;
use Inspector\Inspector;
use Inspector\Models\Error;
use Inspector\Models\Model;
use Inspector\Models\Segment;
use Inspector\Models\Token;
use Inspector\Neuron\V4\InspectorSubscriber;
use Inspector\Transports\TransportInterface;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\Usage;
use NeuronAI\Agent\Observability\InferenceStart;
use NeuronAI\Agent\Observability\InferenceStop;
use NeuronAI\Agent\Observability\ToolCalled;
use NeuronAI\Agent\Observability\ToolCalling;
use NeuronAI\Observability\ObservabilityEvent;
use NeuronAI\Providers\ProviderResponse;
use NeuronAI\Tools\ToolCall;
use NeuronAI\Workflow\Events\Event;
use NeuronAI\Workflow\ExecutionContext;
use NeuronAI\Workflow\Executor\Ignition;
use NeuronAI\Workflow\Interrupt\WaitForEventRequest;
use NeuronAI\Workflow\Middleware\WorkflowMiddleware;
use NeuronAI\Workflow\NodeInterface;
use NeuronAI\Workflow\Observability\BranchEnd;
use NeuronAI\Workflow\Observability\BranchStart;
use NeuronAI\Workflow\Observability\MiddlewareEnd;
use NeuronAI\Workflow\Observability\MiddlewareStart;
use NeuronAI\Workflow\Observability\NodeOutcome;
use NeuronAI\Workflow\Observability\WorkflowEnd;
use NeuronAI\Workflow\Observability\WorkflowError;
use NeuronAI\Workflow\Observability\WorkflowInterrupted;
use NeuronAI\Workflow\Observability\WorkflowNodeEnd;
use NeuronAI\Workflow\Observability\WorkflowNodeStart;
use NeuronAI\Workflow\Observability\WorkflowStart;
use NeuronAI\Workflow\WorkflowResources;
use NeuronAI\Workflow\WorkflowState;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

use function array_filter;
use function array_values;

class InspectorSubscriberTest extends TestCase
{
    public function testWorkflowStartStartsAgentTransaction(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));

        $transaction = $recorder->inspector()->transaction();

        $this->assertNotNull($transaction);
        $this->assertSame(stdClass::class, $transaction->name);
        $this->assertSame('agent', $transaction->type);
        $this->assertSame('success', $transaction->result);
    }

    public function testNodeEventsCreateSegmentWithStateContext(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));

        $state = new WorkflowState();
        $state->set('answer', '42');

        $recorder->dispatch(new WorkflowNodeStart('App\MyNode', $state));
        $recorder->dispatch(new WorkflowNodeEnd('App\MyNode', $state));

        $segment = $recorder->segment('agent.node', 'MyNode');

        $this->assertNotNull($segment);
        $this->assertSame('completed', $segment->getContext()['Outcome']);
        $this->assertSame(['answer' => '42'], $segment->getContext('State Before'));
        $this->assertSame(['answer' => '42'], $segment->getContext('State After'));
        $this->assertNotNull($segment->duration);
    }

    public function testInferenceEventsCreateSegmentWithTokens(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));

        // ChatNode emits both events with the last inbound message, while
        // the provider attaches the usage to the response message only.
        $question = new Message(MessageRole::USER, 'question');

        $response = new Message(MessageRole::ASSISTANT, 'the answer');
        $response->setUsage(new Usage(12, 34));

        $recorder->dispatch(new InferenceStart($question));
        $recorder->dispatch(new InferenceStop($question, new ProviderResponse($response)));

        $segment = $recorder->segment('agent.inference', 'inference( Message )');

        $this->assertNotNull($segment);
        $this->assertNotNull($segment->duration);
        $this->assertSame('question', $segment->getContext()['Message']['content'][0]['content']);

        $token = $recorder->firstOf(Token::class);
        $this->assertInstanceOf(Token::class, $token);
        $this->assertSame(12, $token->input_tokens);
        $this->assertSame(34, $token->output_tokens);
        $this->assertSame($recorder->inspector()->transaction()->hash, $token->transaction['hash']);
    }

    public function testInferenceWithoutUsageReportsNoTokens(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));

        $question = new Message(MessageRole::USER, 'question');
        $response = new Message(MessageRole::ASSISTANT, 'the answer');

        $recorder->dispatch(new InferenceStart($question));
        $recorder->dispatch(new InferenceStop($question, new ProviderResponse($response)));

        $this->assertNotNull($recorder->segment('agent.inference', 'inference( Message )'));
        $this->assertNull($recorder->firstOf(Token::class));
    }

    public function testToolEventsCreateSegmentWithInputsAndOutput(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));

        $call = new ToolCall('search', 'call_1', ['query' => 'inspector']);
        $call->setResult('no results');

        $recorder->dispatch(new ToolCalling($call));
        $recorder->dispatch(new ToolCalled($call));

        $segment = $recorder->segment('agent.tool', 'tool_call( search )');

        $this->assertNotNull($segment);
        $this->assertSame(['query' => 'inspector'], $segment->getContext()['Inputs']);
        $this->assertSame('no results', $segment->getContext()['Output']);
        $this->assertNotNull($segment->duration);
    }

    public function testUnhandledWorkflowErrorIsReportedAndMarksTransaction(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));
        $recorder->dispatch(new WorkflowError(new RuntimeException('boom')));

        $error = $recorder->firstOf(Error::class);
        $this->assertInstanceOf(Error::class, $error);
        $this->assertFalse($error->handled);
        $this->assertSame('error', $recorder->inspector()->transaction()->result);
    }

    public function testWorkflowInterruptedAddsContextNotError(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));

        $recorder->dispatch(new WorkflowInterrupted($this->suspendedState()));

        $this->assertArrayHasKey('Interrupt', $recorder->inspector()->transaction()->getContext());
        $this->assertSame('success', $recorder->inspector()->transaction()->result);
        $this->assertNull($recorder->firstOf(Error::class));
    }

    public function testWorkflowEndFlushesAutomatically(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));
        $recorder->dispatch(new WorkflowEnd(new WorkflowState()));

        $this->assertTrue($recorder->flushed());
    }

    public function testWorkflowEndFlushesAfterError(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));
        $recorder->dispatch(new WorkflowError(new RuntimeException('boom')));

        $transaction = $recorder->inspector()->transaction();

        $recorder->dispatch(new WorkflowEnd(new WorkflowState()));

        $this->assertTrue($recorder->flushed());
        $this->assertSame('error', $transaction->result);
        $this->assertInstanceOf(Error::class, $recorder->firstOf(Error::class));
    }

    public function testWorkflowEndFlushesAfterInterruption(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));
        $recorder->dispatch(new WorkflowInterrupted($this->suspendedState()));

        $transaction = $recorder->inspector()->transaction();

        $recorder->dispatch(new WorkflowEnd($this->suspendedState()));

        $this->assertTrue($recorder->flushed());
        $this->assertArrayHasKey('Interrupt', $transaction->getContext());
        $this->assertSame('suspended', $transaction->getContext()['Status']);
        $this->assertSame('success', $transaction->result);
    }

    public function testFailedRunMarksTransactionAsErrorAtWorkflowEnd(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));

        // The executor reports run failures as handled WorkflowErrors: the
        // failed state status carried by WorkflowEnd decides the result.
        $recorder->dispatch(new WorkflowError(new RuntimeException('boom'), false));

        $transaction = $recorder->inspector()->transaction();
        $this->assertSame('success', $transaction->result);

        $state = new WorkflowState();
        $state->markAsFailed();
        $recorder->dispatch(new WorkflowEnd($state));

        $this->assertTrue($recorder->flushed());
        $this->assertSame('error', $transaction->result);
        $this->assertSame('failed', $transaction->getContext()['Status']);
        $this->assertTrue($recorder->firstOf(Error::class)->handled);
    }

    public function testWorkflowStartAddsExecutionContext(): void
    {
        $recorder = new Recorder();

        $start = new WorkflowStart([]);
        $start->execution = $this->execution('run-1');
        $recorder->dispatch($start);

        $this->assertSame([
            'workflowId' => 'workflow-1',
            'runId' => 'run-1',
            'executionAttempt' => 1,
        ], $recorder->inspector()->transaction()->getContext('Execution'));
    }

    public function testHostOwnedTransactionIsNotFlushed(): void
    {
        $recorder = new Recorder();
        $inspector = $recorder->inspector();

        // The host application started the transaction: the subscriber must
        // not end it, it only opens a workflow segment inside it.
        $inspector->startTransaction('host-request');

        $start = new WorkflowStart([]);
        $start->source = new HostedWorkflowFixture();
        $recorder->dispatch($start);

        $end = new WorkflowEnd(new WorkflowState());
        $end->source = new HostedWorkflowFixture();
        $recorder->dispatch($end);

        $this->assertFalse($recorder->flushed());
        $this->assertNotNull($inspector->transaction());
        $this->assertNotNull($recorder->segment('agent.workflow', 'HostedWorkflowFixture'));
    }

    public function testBranchEventsIsolateSegmentScopes(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));

        $state = new WorkflowState();

        $start = new BranchStart('branch-1');
        $start->source = new stdClass();
        $recorder->dispatch($start);

        $nodeStart = new WorkflowNodeStart('App\BranchNode', $state);
        $nodeStart->branchId = 'branch-1';
        $recorder->dispatch($nodeStart);

        $nodeEnd = new WorkflowNodeEnd('App\BranchNode', $state);
        $nodeEnd->branchId = 'branch-1';
        $recorder->dispatch($nodeEnd);

        $end = new BranchEnd('branch-1');
        $end->source = new stdClass();
        $recorder->dispatch($end);

        $segment = $recorder->segment('agent.node', 'BranchNode');

        $this->assertNotNull($segment);
        $this->assertNotNull($segment->duration);
    }

    public function testMiddlewareEventsCreateSegment(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));

        $state = new WorkflowState();
        $middleware = new MiddlewareFixture();

        $recorder->dispatch(new WorkflowNodeStart('App\MyNode', $state));
        $recorder->dispatch(new MiddlewareStart($middleware, new StartEventFixture()));
        $recorder->dispatch(new MiddlewareEnd($middleware));
        $recorder->dispatch(new WorkflowNodeEnd('App\MyNode', $state));

        $segment = $recorder->segment('agent.middleware', 'MiddlewareFixture::before()');

        $this->assertNotNull($segment);
        $this->assertSame(StartEventFixture::class, $segment->getContext()['Event']);
        $this->assertArrayNotHasKey('Outcome', $segment->getContext());
        $this->assertNotNull($segment->duration);
    }

    public function testFailedOutcomesAreRecordedOnSegments(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));

        $state = new WorkflowState();
        $middleware = new MiddlewareFixture();

        $recorder->dispatch(new WorkflowNodeStart('App\MyNode', $state));
        $recorder->dispatch(new MiddlewareStart($middleware, new StartEventFixture()));
        $recorder->dispatch(new MiddlewareEnd($middleware, 'before', NodeOutcome::Failed));
        $recorder->dispatch(new WorkflowNodeEnd('App\MyNode', $state, NodeOutcome::Failed));

        $this->assertSame('failed', $recorder->segment('agent.middleware', 'MiddlewareFixture::before()')->getContext()['Outcome']);
        $this->assertSame('failed', $recorder->segment('agent.node', 'MyNode')->getContext()['Outcome']);
    }

    public function testConcurrentRunsDoNotShareOpenSegments(): void
    {
        $recorder = new Recorder();

        $recorder->inspector()->startTransaction('host-request');

        $state = new WorkflowState();

        // Two runs of the same node interleave on the shared subscriber:
        // each end event must close the segment opened by its own run.
        $startA = new WorkflowNodeStart('App\MyNode', $state);
        $startA->execution = $this->execution('run-a');
        $recorder->dispatch($startA);

        $startB = new WorkflowNodeStart('App\MyNode', $state);
        $startB->execution = $this->execution('run-b');
        $recorder->dispatch($startB);

        $endA = new WorkflowNodeEnd('App\MyNode', $state);
        $endA->execution = $this->execution('run-a');
        $recorder->dispatch($endA);

        $segments = $recorder->segments('agent.node', 'MyNode');

        $this->assertCount(2, $segments);
        $this->assertNotNull($segments[0]->duration);
        $this->assertNull($segments[1]->duration);
    }

    private function suspendedState(): WorkflowState
    {
        $state = new WorkflowState();
        $state->markAsSuspended(new WaitForEventRequest('approval'));

        return $state;
    }

    private function execution(string $runId): ExecutionContext
    {
        return new ExecutionContext('workflow-1', $runId, 1, new Ignition($runId, new StartEventFixture()));
    }
}

class StartEventFixture implements Event
{
}

class HostedWorkflowFixture
{
}

class MiddlewareFixture implements WorkflowMiddleware
{
    public function before(NodeInterface $node, Event $event, WorkflowState $state, WorkflowResources $resources): void
    {
    }

    public function after(NodeInterface $node, Event $result, WorkflowState $state, WorkflowResources $resources): void
    {
    }
}

/**
 * Test double collecting every entry the observer queues, so assertions run
 * against real models without touching the network.
 */
class Recorder
{
    private Inspector $inspector;

    private InspectorSubscriber $subscriber;

    private CapturingTransport $transport;

    public function __construct()
    {
        $this->inspector = new Inspector(new Configuration('example-ingestion-key'));

        $this->transport = new CapturingTransport();
        $this->inspector->setTransport($this->transport);

        $this->subscriber = new InspectorSubscriber($this->inspector);
    }

    public function dispatch(ObservabilityEvent $event): void
    {
        if ($event->source === null) {
            $event->source = new stdClass();
        }

        ($this->subscriber)($event);
    }

    public function inspector(): Inspector
    {
        return $this->inspector;
    }

    public function flushed(): bool
    {
        return $this->transport->didFlush;
    }

    public function firstOf(string $class): ?object
    {
        return $this->transport->firstOf($class);
    }

    public function segment(string $type, string $label): ?Segment
    {
        return $this->transport->segment($type, $label);
    }

    /**
     * @return array<Segment>
     */
    public function segments(string $type, string $label): array
    {
        return $this->transport->segments($type, $label);
    }
}

class CapturingTransport implements TransportInterface
{
    /** @var array<Model> */
    public array $captured = [];

    public bool $didFlush = false;

    public function addEntry(Model $model): TransportInterface
    {
        $this->captured[] = $model;
        return $this;
    }

    public function resetQueue(): TransportInterface
    {
        $this->captured = [];
        return $this;
    }

    public function flush(): TransportInterface
    {
        $this->didFlush = true;
        return $this;
    }

    public function firstOf(string $class): ?object
    {
        foreach ($this->captured as $entry) {
            if ($entry instanceof $class) {
                return $entry;
            }
        }

        return null;
    }

    public function segment(string $type, string $label): ?Segment
    {
        return $this->segments($type, $label)[0] ?? null;
    }

    /**
     * @return array<Segment>
     */
    public function segments(string $type, string $label): array
    {
        return array_values(array_filter(
            $this->captured,
            fn (Model $entry): bool => $entry instanceof Segment && $entry->type === $type && $entry->label === $label
        ));
    }
}
