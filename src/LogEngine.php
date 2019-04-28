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
     * @var string
     */
    protected $transaction;

    /**
     * Default severity level.
     *
     * @var string
     */
    protected $defaultLevel = LogLevel::DEBUG;

    /**
     * Translates PSR-3 log levels to syslog log severity.
     */
    protected $syslogSeverityMap = array(
        LogLevel::DEBUG     => 7,
        LogLevel::INFO      => 6,
        LogLevel::NOTICE    => 5,
        LogLevel::WARNING   => 4,
        LogLevel::ERROR     => 3,
        LogLevel::CRITICAL  => 2,
        LogLevel::ALERT     => 1,
        LogLevel::EMERGENCY => 0,
    );

    /**
     * Logger constructor.
     *
     * @param null|string $apiKey
     * @param array $options
     * @param int $facility
     * @param string $identity
     * @throws Exceptions\LogEngineException
     */
    public function __construct($apiKey = null, array $options = array(), $facility = LOG_USER, $identity = 'php')
    {
        $this->facility = $facility;
        $this->identity = $identity;
        $this->exceptionEncoder = new ExceptionEncoder();

        switch (getenv('LOGENGINE_TRANSPORT')){
            case 'async':
                $this->transport = new AsyncTransport($apiKey, null, $options);
                break;
            default:
                $this->transport = new CurlTransport($apiKey, null, $options);
        }

        $this->generateTransactionId();
        register_shutdown_function(array($this, 'flush'));
    }

    /**
     * Set a new default severity level.
     *
     * @param string $level
     * @return $this
     */
    public function setSeverityLevel($level)
    {
        if (!in_array($level, array_keys($this->syslogSeverityMap))) {
            syslog(LOG_WARNING, 'LOG Engine Warning: Invalid notify level supplied to LOG Engine Logger');
        } else {
            $this->defaultLevel = $level;
        }
        return $this;
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
        if (!$this->isAboveLevel($level, $this->defaultLevel)) {
            return;
        }

        $entry = $this->makeSyslogHeader($this->syslogSeverityMap[$level]);

        // find exception, remove it from context,
        if (isset($context['exception']) && ($context['exception'] instanceof \Exception || $context['exception'] instanceof \Throwable)) {
            $exception = $context['exception'];
            unset($context['exception']);
        } elseif ($message instanceof \Exception || $message instanceof \Throwable) {
            $exception = $message;
        }

        if(isset($exception)){
            $entry = array_merge($this->exceptionEncoder->exceptionToArray($exception), $entry);
        }

        $this->transport->addEntry(
            $this->assembleMessage($message, $context, $entry)
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

        $this->error($exception, $context);
    }

    /**
     * @param $message
     * @param $context
     * @param $entry
     * @return array
     */
    protected function assembleMessage($message, $context, $entry)
    {
        return array_merge([
            'message' => $message . ' ' . json_encode($context),
            'context' => $context,
            'transaction' => $this->transaction,
        ], $entry);
    }

    /**
     * @param integer $severity
     * @return array
     */
    protected function makeSyslogHeader($severity)
    {
        return [
            'priority' => $this->facility + $severity,
            'timestamp' => date(\DateTime::RFC3339),
            'hostname' => getenv('LOGENGINE_HOSTNAME') ?: gethostname(),
            'identity' => $this->identity,
        ];
    }

    /**
     * Checks whether the selected level is above another level.
     *
     * @param string $level
     * @param string $base
     *
     * @return bool
     */
    protected function isAboveLevel($level, $base)
    {
        $levelOrder = array_keys($this->syslogSeverityMap);
        $baseIndex = array_search($base, $levelOrder);
        $levelIndex = array_search($level, $levelOrder);
        return $levelIndex >= $baseIndex;
    }

    /**
     * Flush all messages queue programmatically.
     * @throws \Exception
     */
    public function flush()
    {
        $this->transport->flush();
        $this->generateTransactionId();
    }

    /**
     * Generate unique ID for grouping events.
     *
     * http://www.php.net/manual/en/function.uniqid.php
     *
     * @param int $length
     * @return string
     * @throws \Exception
     */
    public function generateTransactionId($length = 32)
    {
        if(!isset($length) || intval($length) <= 8 ){
            $length = 32;
        }

        if (function_exists('random_bytes')) {
            $this->transaction =  bin2hex(random_bytes($length));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $this->transaction = bin2hex(openssl_random_pseudo_bytes($length));
        }
    }
}