<?php

namespace LogEngine;


class ExceptionMessage extends LogMessage
{
    /**
     * @var ExceptionEncoder
     */
    protected $encoder;

    /**
     * ExceptionRecord constructor.
     *
     * @param array $errorLog
     * @param bool $handled
     */
    public function __construct(array $errorLog, $handled = false)
    {
        $this->encoder = new ExceptionEncoder();

        $record = array_merge(
            $this->encoder->exceptionToArray($errorLog['exception']),
            array(
                'level' => $errorLog['level'],
                'context' => $errorLog['context'],
                'tag' => 'exception',
                'type' => $handled ? 'handled' : 'unhandled',
            )
        );

        parent::__construct($record);
    }
}