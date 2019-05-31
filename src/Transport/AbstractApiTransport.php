<?php

namespace Inspector\Transport;


use Inspector\Configuration;
use Inspector\Contracts\TransportInterface;
use Inspector\Exceptions\LogEngineApmException;

abstract class AbstractApiTransport implements TransportInterface
{
    /**
     * @var string
     */
    const ERROR_LENGTH = 'Batch is too long: %s';

    /**
     * Key to authenticate remote calls.
     *
     * @var Configuration
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
     * @throws LogEngineApmException
     */
    public function __construct(Configuration $configuration)
    {
        $this->config = $configuration;
        $this->verifyOptions($configuration->getOptions());
    }

    /**
     * Verify if given options match constraints.
     *
     * @param $options
     * @throws LogEngineApmException
     */
    protected function verifyOptions($options)
    {
        foreach ($this->getAllowedOptions() as $name => $regex) {
            if (isset($options[$name])) {
                $value = $options[$name];
                if (preg_match($regex, $value)) {
                    $this->$name = $value;
                } else {
                    throw new LogEngineApmException("Option '$name' has invalid value");
                }
            }
        }
    }

    /**
     * Add a message to the queue.
     *
     * @param $item
     * @return TransportInterface
     */
    public function addEntry($item): TransportInterface
    {
        $this->queue[] = $item;
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
     * Send data chunks based on MAX_POST_LENGTH.
     *
     * @param array $logs
     */
    public function send($logs)
    {
        $json = json_encode($logs);
        $jsonLength = strlen($json);
        $count = count($logs);

        if ($jsonLength > $this->config::MAX_POST_LENGTH) {
            if ($count === 1) {
                // It makes no sense to divide into chunks, just fail silently
                return;
            }
            $maxCount = floor($count / ceil($jsonLength / $this->config::MAX_POST_LENGTH));
            $chunks = array_chunk($logs, $maxCount);
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