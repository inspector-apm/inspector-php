<?php

namespace Inspector\Models;

use Inspector\Models\Partials\Host;

class Error extends Model
{
    public ?string $model = 'error';

    public string|float $timestamp;

    public Host $host;

    public string $message;

    public string $class;

    public string $file;

    public int $line;

    public int|string $code;

    public array $stack;

    public bool $handled = false;

    public array $transaction;

    /**
     * Error constructor.
     *
     * @param \Throwable $throwable
     * @param Transaction $transaction
     */
    public function __construct(\Throwable $throwable, Transaction $transaction)
    {
        $this->timestamp = \microtime(true);

        $this->host = new Host();

        $this->message = $throwable->getMessage()
            ? $throwable->getMessage()
            : \get_class($throwable);

        $this->class = \get_class($throwable);
        $this->file = $throwable->getFile();
        $this->line = $throwable->getLine();
        $this->code = $throwable->getCode();

        $this->stack = $this->stackTraceToArray($throwable);

        $this->transaction = $transaction->only(['name', 'hash']);
    }

    /**
     * Determine if the exception is handled/unhandled.
     */
    public function setHandled(bool $value): Error
    {
        $this->handled = $value;
        return $this;
    }

    /**
     * Serialize stack trace to array
     */
    public function stackTraceToArray(\Throwable $throwable): array
    {
        $stack = [];
        $counter = 0;

        // Exception object `getTrace` does not return file and line number for the first line
        // http://php.net/manual/en/exception.gettrace.php#107563

        $inApp = function ($file) {
            return !\str_contains($file, 'vendor') &&
                !\str_contains($file, 'index.php') &&
                !\str_contains($file, 'web/core'); // Drupal
        };

        $stack[] = [
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'in_app' => $inApp($throwable->getFile()),
            'code' => $this->getCode($throwable->getFile(), $throwable->getLine(), $inApp($throwable->getFile()) ? 15 : 5),
        ];

        foreach ($throwable->getTrace() as $trace) {
            $stack[] = [
                'class' => $trace['class'] ?? null,
                'function' => $trace['function'],
                'args' => $this->stackTraceArgsToArray($trace),
                'type' => $trace['type'] ?? 'function',
                'file' => $trace['file'] ?? '[internal]',
                'line' => $trace['line'] ?? '0',
                'code' => isset($trace['file'])
                    ? $this->getCode($trace['file'], $trace['line'] ?? '0', $inApp($trace['file']) ? 15 : 5)
                    : [],
                'in_app' => isset($trace['file']) && $inApp($trace['file']),
            ];

            // Reporting limit
            if (++$counter >= 50) {
                break;
            }
        }

        return $stack;
    }

    /**
     * Serialize stack trace function arguments
     */
    protected function stackTraceArgsToArray(array $trace): array
    {
        $params = [];

        if (!isset($trace['args'])) {
            return $params;
        }

        foreach ($trace['args'] as $arg) {
            if (\is_array($arg)) {
                $params[] = 'array(' . \count($arg) . ')';
            } elseif (\is_object($arg)) {
                $params[] = \get_class($arg);
            } elseif (\is_string($arg)) {
                $params[] = 'string(' . $arg . ')';
            } elseif (\is_int($arg)) {
                $params[] = 'int(' . $arg . ')';
            } elseif (\is_float($arg)) {
                $params[] = 'float(' . $arg . ')';
            } elseif (\is_bool($arg)) {
                $params[] = 'bool(' . ($arg ? 'true' : 'false') . ')';
            } else {
                $params[] = \gettype($arg);
            }
        }

        return $params;
    }

    /**
     * Extract a code source from file.
     */
    public function getCode(string $filePath, int $line, int $linesAround = 5): ?array
    {
        try {
            $file = new \SplFileObject($filePath);
            $file->setMaxLineLen(250);
            $file->seek(\PHP_INT_MAX);

            $codeLines = [];

            $from = \max(0, $line - $linesAround);
            $to = \min($line + $linesAround, $file->key());

            $file->seek($from);

            while ($file->key() <= $to && !$file->eof()) {
                $codeLines[] = [
                    'line' => $file->key() + 1,
                    'code' => \rtrim($file->current()),
                ];
                $file->next();
            }

            return $codeLines;
        } catch (\Exception $e) {
            return null;
        }
    }
}
