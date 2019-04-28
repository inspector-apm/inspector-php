<?php

namespace LogEngine\Tests;


use LogEngine\ExceptionEncoder;
use PHPUnit\Framework\TestCase;

class ExceptionEncoderTest extends TestCase
{
    public function testExceptionObjectResult()
    {
        $code = 1234;
        $message = 'Test Message';
        $exception = new \DomainException($message, $code);
        $encoder = new ExceptionEncoder();
        $exceptionArray = $encoder->exceptionToArray($exception);
        $stackTraceArray = $encoder->stackTraceToArray($exception->getTrace(), $exception->getFile(), $exception->getLine());
        $this->assertSame($message, $exceptionArray['message']);
        $this->assertSame('DomainException', $exceptionArray['class']);
        $this->assertSame($code, $exceptionArray['code']);
        $this->assertSame(__FILE__, $exceptionArray['file']);
        $this->assertSame(serialize($stackTraceArray), serialize($exceptionArray['stack']));
        $this->assertNotEmpty($exceptionArray['line']);
    }

    public function testStackTraceResult()
    {
        $exception = new \DomainException;
        $encoder = new ExceptionEncoder();
        $originalStackTrace = $exception->getTrace();
        $stackTraceArray = $encoder->stackTraceToArray($originalStackTrace);
        $this->assertSame(count($originalStackTrace), count($stackTraceArray));
        $this->assertSame($originalStackTrace[0]['function'], $stackTraceArray[0]['function']);
        $this->assertSame($originalStackTrace[0]['class'], $stackTraceArray[0]['class']);
    }

    public function testEmptyExceptionMessageCase()
    {
        $exception = new \DomainException;
        $encoder = new ExceptionEncoder();
        $exceptionArray = $encoder->exceptionToArray($exception);
        $this->assertSame('DomainException', $exceptionArray['message']);
    }

    public function testStackTraceSerializationWithoutArgs()
    {
        $stackTrace = debug_backtrace();
        unset($stackTrace[0]['args']);
        $encoder = new ExceptionEncoder();
        $stackTraceArray = $encoder->stackTraceToArray($stackTrace);
        $this->assertEmpty($stackTraceArray[0]['args']);
    }

    public function testUndefinedFunctionIndex()
    {
        $stackTrace = debug_backtrace();
        unset($stackTrace[0]['function']);
        $encoder = new ExceptionEncoder();
        $stackTraceArray = $encoder->stackTraceToArray($stackTrace);
        $this->assertEmpty($stackTraceArray[0]['function']);
    }

    public function testIncompleteClass()
    {
        $catched = false;

        try {
            // trigger invalid argument exception
            // to test __PHP_Incomplete_Class argument serialisation in `stackTraceToArray`
            // `Object of class __PHP_Incomplete_Class could not be converted to string`
            $incompleteObject = unserialize('O:1:"a":1:{s:5:"value";s:3:"100";}');
            (new ExceptionEncoder())->exceptionToArray($incompleteObject);

            $this->fail('Should never be reached');
        } catch (\Exception $exception) {
            $this->assertIncompleteClassStackTrace($exception, 0);
            $catched = true;
        }

        $this->assertTrue($catched);
    }

    /**
     * @param object $exception
     * @param integer $index
     * @return void
     */
    protected function assertIncompleteClassStackTrace($exception, $index)
    {
        $encoder = new ExceptionEncoder();
        $stackTraceArray = $encoder->stackTraceToArray($exception->getTrace());
        if (Utils::startsWith(phpversion(), '7.1')) {
            $this->assertSame('__PHP_Incomplete_Class', $stackTraceArray[$index]['args'][0]);
        } else {
            $this->assertSame('object(__PHP_Incomplete_Class)', $stackTraceArray[$index]['args'][0]);
        }
    }

    public function testStackTraceLimit()
    {
        $exception = new \DomainException;
        $encoder = new ExceptionEncoder();
        $originalStackTrace = $exception->getTrace();
        $stackTraceArray = $encoder->stackTraceToArray($originalStackTrace);
        $this->assertTrue(count($stackTraceArray) > 2);
        $encoder->setStackTraceLimit(2);
        $stackTraceArray2 = $encoder->stackTraceToArray($originalStackTrace);
        $this->assertTrue(count($stackTraceArray2) === 2);
    }
}