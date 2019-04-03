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

        $record = $this->encoder->exceptionToArray($errorLog['exception']);
        $record['level'] = $errorLog['level'];
        $record['context'] = $errorLog['context'];
        $record['tag'] = 'exception';
        $record['type'] = $handled ? 'handled' : 'unhandled';

        parent::__construct($record);
    }
}