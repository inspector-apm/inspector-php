<?php

namespace LogEngine\Transport;


use LogEngine\Contracts\AbstractMessageBag;

class MessageBag extends AbstractMessageBag
{
    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * @return array
     */
    public function getLogs()
    {
        return $this->logs ?? [];
    }

    /**
     * @param string $environment
     * @return MessageBag
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * @param string $hostname
     * @return MessageBag
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
        return $this;
    }

    /**
     * @param array $logs
     * @return MessageBag
     */
    public function setLogs($logs)
    {
        $this->logs = $logs;
        return $this;
    }
}