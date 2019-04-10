<?php

namespace LogEngine\Transport;


use LogEngine\Contracts\LogEntryInterface;
use LogEngine\Contracts\AbstractMessageBag;
use LogEngine\Contracts\TransportInterface;
use LogEngine\Exceptions\LogEngineException;

abstract class AbstractApiTransport implements TransportInterface
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
     * @param string $url
     * @param string $apiKey
     * @param string $environment
     * @param array $options
     * @throws LogEngineException
     */
    public function __construct($url = null, $apiKey = null, $environment = null,  array $options = array())
    {
        parent::__construct();

        $this->config = new Configuration(
            $url ?: getenv('LOGENGINE_URL'),
            $apiKey ?: getenv('LOGENGINE_API_KEY'),
            $environment ?: getenv('LOGENGINE_ENV')
        );

        $this->extractOptions($options);

        register_shutdown_function(array($this, 'flush'));
    }

    /**
     * Verify if given options match constraints.
     *
     * @param $options
     * @throws LogEngineException
     */
    protected function extractOptions($options)
    {
        foreach ($this->getAllowedOptions() as $name => $regex) {
            if (isset($options[$name])) {
                $value = $options[$name];
                if (preg_match($regex, $value)) {
                    $this->$name = $value;
                } else {
                    throw new LogEngineException("Option '$name' has invalid value");
                }
            }
        }
    }

    /**
     * Add a message to the queue.
     *
     * @param LogEntryInterface $log
     * @return TransportInterface
     */
    public function addEntry(LogEntryInterface $log): TransportInterface
    {
        $this->queue[] = $log;
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

        // add latest info
        foreach ($this->queue as $log) {
            $log->merge([
                'environment' => $this->config->getEnvironment(),
                'hostname' => $this->config->getHostname(),
            ]);
        }

        $this->send($this->queue);

        $this->queue = array();
    }

    /**
     * Send data chunks based on MAX_POST_LENGTH.
     *
     * @param array $logs
     */
    protected function send($logs)
    {
        $json = json_encode($logs);
        $jsonLength = strlen($json);
        $count = count($logs);

        if ($jsonLength > self::MAX_POST_LENGTH) {
            if ($count === 1) {
                // it makes no sense to divide into chunks, just fail
                return;
            }
            $maxCount = floor($count / ceil($jsonLength / self::MAX_POST_LENGTH));
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