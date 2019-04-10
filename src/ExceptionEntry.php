<?php

namespace LogEngine;


class ExceptionEntry extends LogEntry
{
    /**
     * @var ExceptionEncoder
     */
    protected $encoder;

    /**
     * ExceptionRecord constructor.
     *
     * @param array $errorLog
     */
    public function __construct(array $errorLog)
    {
        $this->encoder = new ExceptionEncoder();

        $record = $this->encoder->exceptionToArray($errorLog['exception']);
        $record['level'] = $errorLog['level'];
        $record['context'] = $errorLog['context'];
        $record['type'] = 'exception';

        parent::__construct($record);
    }
}