<?php

namespace LogEngine\Contracts;


abstract class AbstractMessageBag implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $environment;

    /**
     * @var string
     */
    protected $hostname;

    /**
     * @var null|array
     */
    protected $logs;

    /**
     * MessageBag constructor.
     *
     * @param null|string $environment
     * @param null|string $hostname
     * @param null|array $logs
     */
    public function __construct($environment, $hostname, $logs = null)
    {
        $this->environment = $environment;
        $this->hostname = $hostname;
        $this->logs = $logs;
    }

    /**
     * @return string
     */
    public abstract function getEnvironment();

    /**
     * @return string
     */
    public abstract function getHostname();

    /**
     * @return array
     */
    public abstract function getLogs();

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'environment' => $this->environment,
            'hostname' => $this->hostname,
            'logs' => $this->logs,
        ];
    }
}