<?php

namespace LogEngine\Tests;


use LogEngine\ApmAgent;
use LogEngine\Configuration;
use LogEngine\Models\Error;
use PHPUnit\Framework\TestCase;

class ExceptionEncoderTest extends TestCase
{
    /**
     * @var ApmAgent
     */
    public $apm;

    /**
     * ExceptionEncoderTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     * @throws \Exception
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->apm = new ApmAgent(new Configuration('example-key'));
        $this->apm->startTransaction('testcase');
    }

    public function testExceptionObjectResult()
    {
        $code = 1234;
        $message = 'Test Message';
        $exception = new \DomainException($message, $code);

        $error = new Error($exception, $this->apm->currentTransaction());
        $errorSerialized = $error->jsonSerialize();

        $this->assertSame($message, $errorSerialized['message']);
        $this->assertSame('DomainException', $errorSerialized['class']);
        $this->assertSame($code, $errorSerialized['code']);
        $this->assertSame(__FILE__, $errorSerialized['file']);
        $this->assertNotEmpty($errorSerialized['line']);
    }

    public function testStackTraceResult()
    {
        $exception = new \DomainException;
        $error = new Error($exception, $this->apm->currentTransaction());
        $errorSerialized = $error->jsonSerialize();

        $originalStackTrace = $exception->getTrace();

        $this->assertSame(count($originalStackTrace), count($errorSerialized['stack']));
        $this->assertSame($originalStackTrace[0]['function'], $errorSerialized['stack'][0]['function']);
        $this->assertSame($originalStackTrace[0]['class'], $errorSerialized['stack'][0]['class']);
    }

    public function testEmptyExceptionMessageCase()
    {
        $exception = new \DomainException;
        $error = new Error($exception, $this->apm->currentTransaction());
        $errorSerialized = $error->jsonSerialize();

        $this->assertSame('DomainException', $errorSerialized['message']);
    }
}