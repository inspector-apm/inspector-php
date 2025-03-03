<?php

namespace Inspector;

use Inspector\Models\Segment;
use NeuronAI\Messages\Message;
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
        ];

        if (\array_key_exists($event, $methods) && $subject instanceof \NeuronAI\AgentInterface) {
            $method = $methods[$event];
            $this->$method($subject, $event, $data);
        }
    }

    public function agentStart(\NeuronAI\AgentInterface $agent, string $event = null, $data = null)
    {
        if (!$this->inspector->isRecording()) {
            return;
        }

        $class = get_class($agent);

        if ($this->inspector->needTransaction()) {
            $this->inspector->startTransaction($class)
                ->addContext(self::CONTEXT_LABEL, $this->getContext($agent));
        } elseif ($this->inspector->canAddSegments()) {
            $this->segments[$class] = $this->inspector->startSegment(self::SEGMENT_TYPE, $class)
                ->setContext($this->getContext($agent))
                ->setColor('#3a5a40');
        }
    }

    public function agentStop(\NeuronAI\AgentInterface $agent, string $event = null, $data = null)
    {
        $class = get_class($agent);

        if (\array_key_exists($class, $this->segments)) {
            $this->segments[$class]
                ->setContext($this->getContext($agent))
                ->end();
        } elseif ($this->inspector->hasTransaction()) {
            $this->inspector->transaction()
                ->setContext($this->getContext($agent));
        }
    }

    public function messageSending(\NeuronAI\AgentInterface $agent, string $event, Message $data)
    {
        if (!$this->inspector->canAddSegments()) {
            return;
        }

        $this->segments[
            $this->getMessageId($data)
        ] = $this->inspector
            ->startSegment(self::SEGMENT_TYPE, get_class($data))
            ->setColor('#3a5a40')
            ->setContext($this->getContext($agent));
    }

    public function messageSent(\NeuronAI\AgentInterface $agent, string $event, Message $data)
    {
        $id = $this->getMessageId($data);

        if (\array_key_exists($id, $this->segments)) {
            $this->segments[$id]->end();
        }
    }

    public function toolCalling(\NeuronAI\AgentInterface $agent, string $event, Tool $tool)
    {
        if (!$this->inspector->canAddSegments()) {
            return;
        }

        $this->segments[
            $tool->getName()
        ] = $this->inspector
            ->startSegment(self::SEGMENT_TYPE, get_class($tool))
            ->setColor('#3a5a40')
            ->setContext($this->getContext($agent));
    }

    public function toolCalled(\NeuronAI\AgentInterface $agent, string $event, Tool $tool)
    {
        if (\array_key_exists($tool->getName(), $this->segments)) {
            $this->segments[$tool->getName()]->end();
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
            }, $agent->tools()),
            'Messages' => $agent->resolveChatHistory()->getMessages(),
        ];
    }

    public function getMessageId(Message $message): string
    {
        return \md5($message->getContent().$message->getRole());
    }
}
