<?php

namespace LogEngine;


use LogEngine\Contracts\TransportInterface;
use LogEngine\Transport\AsyncTransport;
use LogEngine\Transport\CurlTransport;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class LogEngine extends AbstractLogger
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
     * @var ExceptionEncoder
     */
    protected $exceptionEncoder;

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
        $this->exceptionEncoder = new ExceptionEncoder();

        switch (getenv('LOGENGINE_TRANSPORT')){
            case 'async':
                $this->transport = new AsyncTransport($url, $apiKey, $options);
                break;
            default:
                $this->transport = new CurlTransport($url, $apiKey, $options);
        }
    }

    /**
     * Static factory.
     *
     * @param mixed ...$args
     * @return static
     * @throws Exceptions\LogEngineException
     */
    public static function make(...$args)
    {
        return new static(...$args);
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

        // find exception, remove it from context,
        if (isset($context['exception']) && ($context['exception'] instanceof \Exception || $context['exception'] instanceof \Throwable)) {
            $exception = $context['exception'];
            unset($context['exception']);
        } elseif ($message instanceof \Exception || $message instanceof \Throwable) {
            $exception = $message;
        }

        if(isset($exception)){
            $headers = array_merge($this->exceptionEncoder->exceptionToArray($exception), $headers);
        }

        $this->transport->addEntry(
            $this->assembleMessage(compact('message', 'context'), $headers)
        );
    }

    /**
     * Direct log an Exception object.
     *
     * @param \Exception $exception
     * @param array $context
     * @return void
     * @throws \InvalidArgumentException
     */
    public function logException($exception, array $context = array())
    {
        if (!$exception instanceof \Exception && !$exception instanceof \Throwable) {
            throw new \InvalidArgumentException('$exception need to be a PHP Exception instance.');
        }

        $this->log(LogLevel::ERROR, $exception, $context);
    }

    /**
     * @param $record
     * @param $header
     * @return array
     */
    protected function assembleMessage($record, $header)
    {
        return array_merge($record, $header);
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