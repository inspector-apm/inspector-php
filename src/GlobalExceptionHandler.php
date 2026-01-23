<?php

namespace Inspector;

class GlobalExceptionHandler
{
    /**
     * @var callable|null
     */
    protected $previousHandler;

    // You can now inject dependencies!
    public function __construct(protected Inspector $inspector)
    {
        // Calling set_exception_handler() returns the previous handler, if any.
        $this->previousHandler = set_exception_handler([$this, 'handleException']);
    }

    /**
     * @throws \Exception
     */
    public function handleException(\Throwable $e): void
    {
        $this->inspector->reportException($e, false);

        // Chain to the previous handler (if any)
        if ($this->previousHandler) {
            call_user_func($this->previousHandler, $e);
        }
    }
}
