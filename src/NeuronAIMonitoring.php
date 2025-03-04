<?php

namespace Inspector;

use Inspector\Models\Segment;
use NeuronAI\Events\InstructionsChanged;
use NeuronAI\Events\InstructionsChanging;
use NeuronAI\Events\MessageSending;
use NeuronAI\Events\MessageSent;
use NeuronAI\Events\ToolCalled;
use NeuronAI\Events\ToolCalling;
use NeuronAI\Events\VectorStoreResult;
use NeuronAI\Events\VectorStoreSearching;
use NeuronAI\Messages\AbstractMessage;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

class NeuronAIMonitoring implements \SplObserver
{
    const SEGMENT_TYPE = 'neuron-ai';
    const CONTEXT_LABEL = 'NeuronAI';

    /**
     * @var Inspector
     */
    protected $inspector;

    /**
     * @var array<string, Segment>
     */
    protected $segments = [];

    public function __construct(Inspector $inspector)
    {
        $this->inspector = $inspector;
    }

    public function update(\SplSubject $subject, string $event = null, $data = null): void
    {
        $methods = [
            'agent:start' => "agentStart",
            'agent:stop' => "agentStop",
            'message:sending' => "messageSending",
            'message:sent' => "messageSent",
            'tool:calling' => "toolCalling",
            'tool:called' => "toolCalled",
            'rag:vectorstore:searching' => "vectorStoreSearching",
            'rag:vectorstore:result' => "vectorStoreResult",
            'rag:instructions:changing' => "instructionsChanging",
            'rag:instructions:changed' => "instructionsChanged",
        ];

        if (!\is_null($event) && \array_key_exists($event, $methods) && $subject instanceof \NeuronAI\AgentInterface) {
            $method = $methods[$event];
            $this->$method($subject, $event, $data);
        }
    }

    public function agentStart(\NeuronAI\AgentInterface $agent, string $event, $data = null)
    {
        if (!$this->inspector->isRecording()) {
            return;
        }

        $class = get_class($agent);

        if ($this->inspector->needTransaction()) {
            $this->inspector->startTransaction($class)
                ->addContext(self::CONTEXT_LABEL, $this->getContext($agent));
        } elseif ($this->inspector->canAddSegments()) {
            $this->segments[$class] = $this->inspector->startSegment(self::SEGMENT_TYPE.':agent', $class)
                ->setContext($this->getContext($agent))
                ->setColor('#3a5a40');
        }
    }

    public function agentStop(\NeuronAI\AgentInterface $agent, string $event, $data = null)
    {
        $class = get_class($agent);

        if (\array_key_exists($class, $this->segments)) {
            $this->segments[$class]
                ->setContext($this->getContext($agent))
                ->end();
        }
    }

    public function messageSending(\NeuronAI\AgentInterface $agent, string $event, MessageSending $data)
    {
        if (!$this->inspector->canAddSegments()) {
            return;
        }

        $this->segments[
            $this->getMessageId($data->message)
        ] = $this->inspector
            ->startSegment(self::SEGMENT_TYPE.':chat', get_class($data->message))
            ->setColor('#3a5a40')
            ->setContext($this->getContext($agent));
    }

    public function messageSent(\NeuronAI\AgentInterface $agent, string $event, MessageSent $data)
    {
        $id = $this->getMessageId($data->message);

        if (\array_key_exists($id, $this->segments)) {
            $this->segments[$id]->end();
        }
    }

    public function toolCalling(\NeuronAI\AgentInterface $agent, string $event, ToolCalling $data)
    {
        if (!$this->inspector->canAddSegments()) {
            return;
        }

        $tool = $data->toolCall->getTool();

        $this->segments[
            $tool->getName()
        ] = $this->inspector
            ->startSegment(self::SEGMENT_TYPE.':tools', $tool->getName())
            ->setColor('#3a5a40')
            ->setContext($this->getContext($agent));
    }

    public function toolCalled(\NeuronAI\AgentInterface $agent, string $event, ToolCalled $data)
    {
        $tool = $data->toolCall->getTool();

        if (\array_key_exists($tool->getName(), $this->segments)) {
            $this->segments[$tool->getName()]->end();
        }
    }

    public function vectorStoreSearching(\NeuronAI\AgentInterface $agent, string $event, VectorStoreSearching $data)
    {
        if (!$this->inspector->canAddSegments()) {
            return;
        }

        $id = \md5($data->question);

        $this->segments[
            $id
        ] = $this->inspector
            ->startSegment(self::SEGMENT_TYPE.':vector-search', $data->question)
            ->setColor('#3a5a40')
            ->setContext($this->getContext($agent));
    }

    public function vectorStoreResult(\NeuronAI\AgentInterface $agent, string $event, VectorStoreResult $data)
    {
        $id = \md5($data->question);

        if (\array_key_exists($id, $this->segments)) {
            $this->segments[$id]->end();
        }
    }

    public function instructionsChanging(\NeuronAI\AgentInterface $agent, string $event, InstructionsChanging $data)
    {
        if (!$this->inspector->canAddSegments()) {
            return;
        }

        $id = \md5($data->instructions);

        $this->segments[
            $id
        ] = $this->inspector
            ->startSegment(self::SEGMENT_TYPE.':instructions', $data->instructions)
            ->setColor('#3a5a40')
            ->setContext($this->getContext($agent));
    }

    public function instructionsChanged(\NeuronAI\AgentInterface $agent, string $event, InstructionsChanged $data)
    {
        $id = \md5($data->instructions);

        if (\array_key_exists($id, $this->segments)) {
            $this->segments[$id]->end();
        }
    }

    protected function getContext(\NeuronAI\AgentInterface $agent): array
    {
        return [
            'Agent' => [
                'instructions' => $agent->instructions(),
                'provider' => get_class($agent->provider()),
            ],
            'Tools' => \array_map(function (Tool $tool) {
                return [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'properties' => \array_map(function (ToolProperty $property) {
                        return $property->toArray();
                    }, $tool->getProperties()),
                ];
            }, $agent->tools()??[]),
            'Messages' => $agent->resolveChatHistory()->getMessages(),
        ];
    }

    public function getMessageId(AbstractMessage $message): string
    {
        return \md5($message->getContent().$message->getRole());
    }
}
