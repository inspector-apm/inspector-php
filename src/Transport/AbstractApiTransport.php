<?php

namespace LogEngine\Transport;


use LogEngine\Contracts\LogEntryInterface;
use LogEngine\Contracts\AbstractMessageBag;
use LogEngine\Contracts\TransportInterface;
use LogEngine\Exceptions\LogEngineException;

abstract class AbstractApiTransport extends AbstractTransport
{
    /**
     * Max size of a POST request content.
     *
     * @var integer
     */
    const MAX_POST_LENGTH = 65536;  // 1024 * 64

    /**
     * @var string
     */
    const ERROR_LENGTH = 'Batch is too long: %s';

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

        $this->send(new MessageBag(
            $this->config->getEnvironment(),
            $this->config->getHostname(),
            $this->queue
        ));

        $this->queue = array();
    }

    /**
     * Send data chunks based on MAX_POST_LENGTH.
     *
     * @param AbstractMessageBag $message
     */
    protected function send($message)
    {
        $json = json_encode($message);
        $jsonLength = strlen($json);
        $count = count($message->getLogs());

        if ($jsonLength > self::MAX_POST_LENGTH) {
            if (1 === $count) {
                // it makes no sense to divide into chunks, just fail
                $this->logError(self::ERROR_LENGTH, $jsonLength);
                return;
            }
            $maxCount = floor($count / ceil($jsonLength / self::MAX_POST_LENGTH));
            $chunks = array_chunk($message->getLogs(), $maxCount);
            foreach ($chunks as $chunk) {
                $this->send($chunk);
            }
        } else {
            $this->sendChunk($json);
        }
    }

    /**
     * Send a portion of the load to the remote service.
     *
     * @param string $data
     * @return void
     */
    abstract protected function sendChunk($data);

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