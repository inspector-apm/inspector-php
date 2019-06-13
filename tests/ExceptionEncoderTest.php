<?php

namespace Inspector\Tests;


use Inspector\Inspector;
use Inspector\Configuration;
use Inspector\Models\Error;
use PHPUnit\Framework\TestCase;

class ExceptionEncoderTest extends TestCase
{
    /**
     * @var Inspector
     */
    public $inspector;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @throws \Exception
     */
    public function setUp()
    {
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false);
        $this->inspector = new Inspector($configuration);
        $this->inspector->startTransaction('testcase');
    }

    public function testExceptionObjectResult()
    {
        $code = 1234;
        $message = 'Test Message';
        $exception = new \DomainException($message, $code);

        $error = new Error($exception, $this->inspector->currentTransaction());
        $error->start()->end();
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
        $error = new Error($exception, $this->inspector->currentTransaction());
        $error->start()->end();

        $errorSerialized = $error->toArray();
        $originalStackTrace = $exception->getTrace();

        // Not contains vendor folder
        $vendor = false;
        foreach ($errorSerialized['stack'] as $stack){
            if(array_key_exists('file', $stack) && strpos($stack['file'], 'vendor') !== false){
                $vendor = true;
                break;
            }
        }
        $this->assertFalse($vendor);

        $this->assertSame($originalStackTrace[0]['function'], $errorSerialized['stack'][0]['function']);
        $this->assertSame($originalStackTrace[0]['class'], $errorSerialized['stack'][0]['class']);
    }

    public function testEmptyExceptionMessageCase()
    {
        $exception = new \DomainException;
        $error = new Error($exception, $this->inspector->currentTransaction());
        $errorSerialized = $error->jsonSerialize();

        $this->assertSame('DomainException', $errorSerialized['message']);
    }
}