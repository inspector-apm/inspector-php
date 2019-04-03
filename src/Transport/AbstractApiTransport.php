<?php

namespace LogEngine\Transport;


use LogEngine\Contracts\LogEntryInterface;
use LogEngine\Contracts\TransportInterface;
use LogEngine\Exceptions\LogEngineException;

abstract class AbstractApiTransport extends AbstractTransport
{
    /**
     * Key to authenticate remote calls.
     *
     * @var string
     */
    protected $config;

    /**
     * Custom url of the proxy if needed.
     *
     * @var string
     */
    protected $proxy;

    /**
     * Queue of messages to send.
     *
     * @var array
     */
    protected $queue = [];

    /**
     * AbstractApiTransport constructor.
     *
     * @param Configuration $configuration
     * @param array $options
     * @throws LogEngineException
     */
    public function __construct($configuration,  array $options = array())
    {
        parent::__construct();

        $this->config = $configuration;

        $this->extractOptions($options);

        register_shutdown_function(array($this, 'flush'));
    }

    /**
     * @param LogEntryInterface $log
     * @return TransportInterface
     */
    public function addEntry(LogEntryInterface $log): TransportInterface
    {
        $this->queue[] = $log;
        $this->logDebug("Message added to the queue: {$log}");
        return $this;
    }

    /**
     * Deliver everything on the queue to LOG Engine.
     *
     * @return void
     */
    public function flush()
    {
        if (empty($this->queue)) {
            return;
        }

        $this->send($this->queue);

        $this->queue = array();
    }

    /**
     * Build the request data to send.
     *
     * @param $data
     * @return string
     */
    protected function buildRequestData($data)
    {
        return json_encode([
            'environment' => $this->config->getEnvironment(),
            'hostname' => $this->config->getHostname(),
            'logs' => $data,
        ]);
    }

    /**
     * Deliver items to LOG Engine.
     *
     * @param mixed $items
     * @return mixed
     */
    protected abstract function send($items);

    /**
     * List of available transport's options with validation regex.
     *
     * ['param-name' => 'regex']
     *
     * @return mixed
     */
    protected function getAllowedOptions()
    {
        return [
            'proxy' => '/.+/', // Custom url for
            'debug' => '/^(0|1)?$/',  // boolean
        ];
    }

    /**
     * @return array
     */
    protected function getApiHeaders()
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Logengine-Key' => $this->config->getApiKey(),
        ];
    }
}