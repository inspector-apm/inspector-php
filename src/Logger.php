<?php

namespace LogEngine;


use LogEngine\Contracts\LogFormatterInterface;
use LogEngine\Contracts\TransportInterface;
use LogEngine\Transport\AsyncTransport;
use LogEngine\Transport\Configuration;
use LogEngine\Transport\CurlTransport;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class Logger extends AbstractLogger
{
    /**
     * @var int
     */
    public $facility;

    /**
     * @var string
     */
    public $identity;

    /**
     * Transport strategy.
     *
     * @var TransportInterface
     */
    protected $transport;

    /**
     * Translates PSR-3 log levels to syslog log severity.
     */
    protected $syslogSeverityMap = array(
        LogLevel::DEBUG     => LOG_DEBUG,
        LogLevel::INFO      => LOG_INFO,
        LogLevel::NOTICE    => LOG_NOTICE,
        LogLevel::WARNING   => LOG_WARNING,
        LogLevel::ERROR     => LOG_ERR,
        LogLevel::CRITICAL  => LOG_CRIT,
        LogLevel::ALERT     => LOG_ALERT,
        LogLevel::EMERGENCY => LOG_EMERG,
    );

    /**
     * Logger constructor.
     *
     * @param null|string $url
     * @param null|string $apiKey
     * @param array $options
     * @param int $facility
     * @param string $identity
     * @throws Exceptions\LogEngineException
     */
    public function __construct($url = null, $apiKey = null, array $options = array(), $facility = LOG_USER, $identity = 'php')
    {
        $this->facility = $facility;
        $this->identity = $identity;

        switch (getenv('LOGENGINE_TRANSPORT')){
            case 'async':
                $this->transport = new AsyncTransport($url, $apiKey, $options);
                break;
            default:
                $this->transport = new CurlTransport($url, $apiKey, $options);
        }
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     * @throws \InvalidArgumentException
     */
    public function log($level, $message, array $context = array())
    {
        $headers = $this->makeSyslogHeader($this->syslogSeverityMap[$level]);

        $this->transport->send(
            $this->assembleMessage(compact('level', 'message', 'context'), $headers)
        );
    }

    /**
     * @param $record
     * @param $header
     * @return string
     */
    protected function assembleMessage($record, $header)
    {
        return json_encode(array_merge($record, $header));
    }


    /**
     * @param integer $severity
     * @return array
     */
    protected function makeSyslogHeader($severity)
    {
        $priority = $this->facility*8 + $severity;

        return [
            'priority' => $priority,
            'timestamp' => date(\DateTime::RFC3339),
            'hostname' => gethostname(),
            'identity' => $this->identity,
        ];
    }
}