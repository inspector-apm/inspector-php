<?php

namespace LogEngine\Transport;


use LogEngine\Contracts\LogFormatterInterface;
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
     * @param string $url
     * @param string $apiKey
     * @param array $options
     * @throws LogEngineException
     */
    public function __construct($url = null, $apiKey = null,  array $options = array())
    {
        $this->config = new Configuration(
            $url ?: getenv('LOGENGINE_URL'),
            $apiKey ?: getenv('LOGENGINE_API_KEY')
        );

        $this->extractOptions($options);
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
     * Send a portion of the load to the remote service.
     *
     * @param string $data
     * @return void
     */
    abstract public function send($data);

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