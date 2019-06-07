<?php


namespace Inspector\Models;


use Inspector\Models\Context\ErrorContext;

class Error extends AbstractModel
{
    const MODEL_NAME = 'error';

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * @var \Throwable
     */
    protected $throwable;

    /**
     * @var array
     */
    protected $stack;

    /**
     * @var ErrorContext
     */
    protected $context;

    /**
     * Error constructor.
     *
     * @param $throwable
     * @param $transaction
     */
    public function __construct($throwable, $transaction)
    {
        $this->throwable = $throwable;
        $this->transaction = $transaction;
        $this->context = new ErrorContext();
    }

    public function getContext(): ErrorContext
    {
        return $this->context;
    }

    public function withUser($id, $username = null, $email = null): Error
    {
        $this->getContext()->getUser()
            ->setId($id)
            ->setUsername($username)
            ->setEmail($email);

        return $this;
    }

    public function start(): AbstractModel
    {
        parent::start();

        $this->stack = $this->stackTraceToArray(
            $this->throwable->getTrace(),
            $this->throwable->getFile(),
            $this->throwable->getLine()
        );

        return $this;
    }

    /**
     * Serialize stack trace to array
     *
     * @param array $stackTrace
     * @param null|string $topFile
     * @param null|string $topLine
     * @return array
     */
    public function stackTraceToArray(array $stackTrace, $topFile = null, $topLine = null)
    {
        $stack = [];
        $counter = 0;

        foreach ($stackTrace as $trace) {
            // Exception object `getTrace` does not return file and line number for the first line
            // http://php.net/manual/en/exception.gettrace.php#107563
            if (isset($topFile, $topLine) && $topFile && $topLine) {
                $trace['file'] = $topFile;
                $trace['line'] = $topLine;

                unset($topFile, $topLine);
            }

            $stack[] = [
                'class' => isset($trace['class']) ? $trace['class'] : null,
                'function' => isset($trace['function']) ? $trace['function'] : null,
                'args' => $this->stackTraceArgsToArray($trace),
                'type' => $trace['type'] ?? 'function',
                'file' => isset($trace['file']) ? basename($trace['file']) : '(unknown)',
                'line' => $trace['line'] ?? '0',
                'code' => isset($trace['file']) ? $this->getCode($trace['file'], $trace['line'] ?? '0') : '(unknown)',
            ];

            $counter++;

            // Reporting limit
            if ($counter >= 100) {
                break;
            }
        }

        return $stack;
    }

    /**
     * Serialize stack trace function arguments
     *
     * @param array $trace
     * @return array
     */
    protected function stackTraceArgsToArray(array $trace)
    {
        $params = [];

        if (!isset($trace['args'])) {
            return $params;
        }

        foreach ($trace['args'] as $arg) {
            if (is_array($arg)) {
                $params[] = 'array(' . count($arg) . ')';
            } else if (is_object($arg)) {
                $params[] = get_class($arg);
            } else if (is_string($arg)) {
                $params[] = 'string(' . $arg . ')';
            } else if (is_int($arg)) {
                $params[] = 'int(' . $arg . ')';
            } else if (is_float($arg)) {
                $params[] = 'float(' . $arg . ')';
            } else if (is_bool($arg)) {
                $params[] = 'bool(' . ($arg ? 'true' : 'false') . ')';
            } else if ($arg instanceof \__PHP_Incomplete_Class) {
                $params[] = 'object(__PHP_Incomplete_Class)';
            } else {
                $params[] = gettype($arg);
            }
        }

        return $params;
    }

    /**
     * Extract code source from file.
     *
     * @param $filePath
     * @param $line
     * @param int $linesAround
     * @return mixed
     */
    public function getCode($filePath, $line, $linesAround = 5)
    {
        if(!$filePath || !$line){
            return null;
        }

        try {
            $file = new \SplFileObject($filePath);
            $file->setMaxLineLen(250);
            $file->seek(PHP_INT_MAX);

            $codeLines = [];

            $from = max(0, $line - $linesAround);
            $to = min($line + $linesAround, $file->key() + 1);

            $file->seek($from);

            while ($file->key() < $to && !$file->eof()) {
                $file->next();
                // `key()` returns 0 as the first line
                $codeLines[] = [
                    'line' => $file->key() + 1,
                    'code' => rtrim($file->current()),
                ];
            }

            return $codeLines;
        }
        catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        $className = get_class($this->throwable);
        $message = $this->throwable->getMessage() ? $this->throwable->getMessage() : $className;

        return [
            'model' => self::MODEL_NAME,
            'message' => $message,
            'timestamp' => $this->timestamp,
            'duration' => $this->duration,
            'file' => $this->throwable->getFile(),
            'class' => $className,
            'code' => $this->throwable->getCode(),
            'line' => $this->throwable->getLine(),
            'stack' => $this->stack,
            'transaction' => $this->transaction->getHash(),
            'group_hash' => md5($className.$this->throwable->getFile().$this->throwable->getLine()),
            'context' => $this->context->jsonSerialize(),
        ];
    }
}