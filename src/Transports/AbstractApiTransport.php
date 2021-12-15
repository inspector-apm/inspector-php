<?php

namespace Inspector\Transports;


use Inspector\Configuration;
use Inspector\Exceptions\InspectorException;
use Inspector\Models\Arrayable;
use Inspector\OS;

abstract class AbstractApiTransport implements TransportInterface
{
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
     * @throws InspectorException
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
     * @throws InspectorException
     */
    protected function verifyOptions($options)
    {
        foreach ($this->getAllowedOptions() as $name => $regex) {
            if (isset($options[$name])) {
                $value = $options[$name];
                if (preg_match($regex, $value)) {
                    $this->$name = $value;
                } else {
                    throw new InspectorException("Option '$name' has invalid value");
                }
            }
        }
    }

    /**
     * Get the current queue.
     *
     * @return array
     */
    public function getQueue(): array
    {
        return $this->queue;
    }

    /**
     * Add a message to the queue.
     *
     * @param array|Arrayable $item
     * @return TransportInterface
     */
    public function addEntry($item): TransportInterface
    {
        if(count($this->queue) <= $this->config->getMaxItems()) {
            $this->queue[] = $item;
        }
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

        $this->queue = [];
    }

    /**
     * Send data chunks based on MAX_POST_LENGTH.
     *
     * @param array $items
     * @return void
     */
    public function send($items)
    {
        $json = \json_encode($items);
        $jsonLength = \strlen($json);
        $count = \count($items);

        if ($jsonLength > $this->config->getMaxPostSize()) {
            if ($count === 1) {
                // It makes no sense to divide into chunks, just try to send data via file
                return $this->sendViaFile(\base64_encode($json));
            }

            $chunkSize = \floor($count / \ceil($jsonLength / $this->config->getMaxPostSize()));
            $chunks = \array_chunk($items, $chunkSize > 0 ? $chunkSize : 1);

            foreach ($chunks as $chunk) {
                $this->send($chunk);
            }
        } else {
            $this->sendChunk(\base64_encode($json));
        }
    }

    /**
     * Put data into a file and provide CURL with the file path.
     *
     * @param string $data
     * @return void
     */
    protected function sendViaFile($data)
    {
        /*$filepath = \uniqid().'.dat';

        if (OS::isWin()) {
            $filepath = __DIR__.DIRECTORY_SEPARATOR.$filepath;
        } else {
            $filepath = '/tmp'.DIRECTORY_SEPARATOR.$filepath;
        }

        \file_put_contents($filepath, $data, LOCK_EX);*/

        $tmpfile = tempnam(sys_get_temp_dir(), 'inspector');
        file_put_contents($tmpfile, $data, LOCK_EX);

        $this->sendChunk('@'.$tmpfile);
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
            'X-Inspector-Key' => $this->config->getIngestionKey(),
            'X-Inspector-Version' => $this->config->getVersion(),
        ];
    }
}
