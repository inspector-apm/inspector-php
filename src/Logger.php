<?php

namespace LogEngine;


use LogEngine\Contracts\TransportInterface;
use LogEngine\Transport\AsyncTransport;
use LogEngine\Exceptions\LogEngineException;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class Logger extends AbstractLogger
{
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
     * Logger constructor.
     *
     * @param TransportInterface $transport
     * @param bool $logServerVariables
     * @throws LogEngineException
     */
    public function __construct(TransportInterface $transport = null, $logServerVariables = false)
    {
        $this->transport = $transport ?? new AsyncTransport();
        $this->exceptionEncoder = new ExceptionEncoder();
    }

    /**
     * Catch object destruction.
     */
    public function __destruct()
    {
        $this->transport->flush();
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     * @throws LogEngineException
     */
    public function log($level, $message, array $context = array())
    {
        // find exception, remove it from context,
        if (isset($context['exception']) && ($context['exception'] instanceof \Exception || $context['exception'] instanceof \Throwable)) {
            $exception = $context['exception'];
            unset($context['exception']);
        } elseif ($message instanceof \Exception || $message instanceof \Throwable) {
            $exception = $message;
        }

        if (isset($exception) && $exception !== null) {
            $this->logException($exception, $context, false);
        } else {
            $this->transport->addEntry(new LogMessage(compact('level', 'message', 'context')));
        }
    }

    /**
     * Logs directly an Exception object.
     *
     * @param \Exception $exception
     * @param array $context
     * @param bool $handled
     * @return void
     * @throws LogEngineException
     */
    public function logException($exception, array $context = array(), $handled = true)
    {
        if (!$exception instanceof \Exception && !$exception instanceof \Throwable) {
            throw new LogEngineException('$exception need to be a PHP Exception instance.');
        }

        $log = new ExceptionMessage([
            'level' => LogLevel::ERROR,
            'exception' => $exception,
            'context' => $context,
        ], $handled);

        $this->transport->addEntry($log);
    }
}