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
use NeuronAI\Observability\Events\AgentError;
use NeuronAI\Observability\Events\BranchEnd;
use NeuronAI\Observability\Events\BranchStart;
use NeuronAI\Observability\Events\InferenceStart;
use NeuronAI\Observability\Events\InferenceStop;
use NeuronAI\Observability\Events\MiddlewareEnd;
use NeuronAI\Observability\Events\MiddlewareStart;
use NeuronAI\Observability\Events\ToolCalled;
use NeuronAI\Observability\Events\ToolCalling;
use NeuronAI\Observability\Events\WorkflowEnd;
use NeuronAI\Observability\Events\WorkflowInterrupted;
use NeuronAI\Observability\Events\WorkflowNodeEnd;
use NeuronAI\Observability\Events\WorkflowNodeStart;
use NeuronAI\Observability\Events\WorkflowStart;
use NeuronAI\Observability\ObservabilityEvent;
use NeuronAI\Providers\ProviderResponse;
use NeuronAI\Tools\ToolCall;
use NeuronAI\Workflow\Events\Event;
use NeuronAI\Workflow\Middleware\WorkflowMiddleware;
use NeuronAI\Workflow\NodeInterface;
use NeuronAI\Workflow\WorkflowState;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

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
        $this->assertSame(['answer' => '42'], $segment->getContext('State Before'));
        $this->assertSame(['answer' => '42'], $segment->getContext('State After'));
        $this->assertNotNull($segment->duration);
    }

    public function testInferenceEventsCreateSegmentWithTokens(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));

        $response = new Message(MessageRole::ASSISTANT, 'the answer');
        $response->setUsage(new Usage(12, 34));

        $recorder->dispatch(new InferenceStart(new Message(MessageRole::USER, 'question')));
        $recorder->dispatch(new InferenceStop($response, new ProviderResponse($response)));

        $segment = $recorder->segment('agent.inference', 'inference( Message )');

        $this->assertNotNull($segment);
        $this->assertNotNull($segment->duration);
        $this->assertSame('the answer', $segment->getContext()['Message']['content'][0]['content']);

        $token = $recorder->firstOf(Token::class);
        $this->assertInstanceOf(Token::class, $token);
        $this->assertSame(12, $token->input_tokens);
        $this->assertSame(34, $token->output_tokens);
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

    public function testAgentErrorIsReportedAndMarksTransaction(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));
        $recorder->dispatch(new AgentError(new RuntimeException('boom')));

        $error = $recorder->firstOf(Error::class);
        $this->assertInstanceOf(Error::class, $error);
        $this->assertFalse($error->handled);
        $this->assertSame('error', $recorder->inspector()->transaction()->result);
    }

    public function testWorkflowInterruptedAddsContextNotError(): void
    {
        $recorder = new Recorder();

        $recorder->dispatch(new WorkflowStart([]));

        $recorder->dispatch(new WorkflowInterrupted(new WorkflowState()));

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
        $recorder->dispatch(new AgentError(new RuntimeException('boom')));

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
        $recorder->dispatch(new WorkflowInterrupted(new WorkflowState()));

        $transaction = $recorder->inspector()->transaction();

        $recorder->dispatch(new WorkflowEnd(new WorkflowState()));

        $this->assertTrue($recorder->flushed());
        $this->assertArrayHasKey('Interrupt', $transaction->getContext());
        $this->assertSame('success', $transaction->result);
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
        $this->assertNotNull($segment->duration);
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
    public function before(NodeInterface $node, Event $event, WorkflowState $state): void
    {
    }

    public function after(NodeInterface $node, Event $result, WorkflowState $state): void
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
        foreach ($this->captured as $entry) {
            if ($entry instanceof Segment && $entry->type === $type && $entry->label === $label) {
                return $entry;
            }
        }

        return null;
    }
}
