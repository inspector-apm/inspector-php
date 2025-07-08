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
    public Inspector $inspector;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @throws \Exception
     */
    public function setUp(): void
    {
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false);
        $this->inspector = new Inspector($configuration);
        $this->inspector->startTransaction('transaction-test');
    }

    public function testExceptionObjectResult()
    {
        $code = 1234;
        $message = 'Test Message';
        $exception = new \DomainException($message, $code);

        $error = new Error($exception, $this->inspector->transaction());

        $this->assertSame($message, $error->message);
        $this->assertSame('DomainException', $error->class);
        $this->assertSame($code, $error->code);
        $this->assertSame(__FILE__, $error->file);
        $this->assertNotEmpty($error->line);
    }

    public function testStackTraceResult()
    {
        $exception = new \DomainException();
        $error = new Error($exception, $this->inspector->currentTransaction());
        $originalStackTrace = $exception->getTrace();

        // Contains vendor folder
        $vendor = false;
        foreach ($error->stack as $stack) {
            if (\array_key_exists('file', $stack) && \str_contains($stack['file'], 'vendor')) {
                $vendor = true;
                break;
            }
        }
        $this->assertTrue($vendor);

        $this->assertSame($originalStackTrace[0]['class'], $error->stack[1]['class']);
    }

    public function testEmptyExceptionMessageCase()
    {
        $exception = new \DomainException();
        $error = new Error($exception, $this->inspector->transaction());

        $this->assertSame('DomainException', $error->message);
    }
}
