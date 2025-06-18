<?php

namespace Inspector\Tests;

use Inspector\Inspector;
use Inspector\Configuration;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    public Inspector $inspector;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @throws \Exception
     */
    public function setUp(): void
    {
        $configuration = new Configuration('example-api-key');
        $configuration->setEnabled(false);

        $this->inspector = new Inspector($configuration);
        $this->inspector->startTransaction('testcase');
    }

    public function testTransactionData()
    {
        $this->assertSame('testcase', $this->inspector->transaction()->name);
        $this->assertSame('request', $this->inspector->transaction()->setType('request')->type);
    }

    public function testSegmentData()
    {
        $segment = $this->inspector->startSegment(__FUNCTION__, 'hello segment!');

        $this->assertSame(__FUNCTION__, $segment->type);
        $this->assertSame('hello segment!', $segment->label);
        $this->assertSame($this->inspector->transaction()->only(['name', 'hash', 'timestamp']), $segment->transaction);
    }

    public function testErrorData()
    {
        $error = $this->inspector->reportException(new \Exception('test error'));
        $error_arr = $error->jsonSerialize();

        $this->assertArrayHasKey('message', $error_arr);
        $this->assertArrayHasKey('stack', $error_arr);
        $this->assertArrayHasKey('file', $error_arr);
        $this->assertArrayHasKey('line', $error_arr);
        $this->assertArrayHasKey('code', $error_arr);
        $this->assertArrayHasKey('class', $error_arr);
        $this->assertArrayHasKey('timestamp', $error_arr);
        $this->assertArrayHasKey('host', $error_arr);

        $this->assertSame($this->inspector->transaction()->only(['name', 'hash']), $error->transaction);
    }

    public function testSetContext()
    {
        $this->inspector->transaction()->addContext('test', ['foo' => 'bar']);

        $this->assertEquals(['test' => ['foo' => 'bar']], $this->inspector->transaction()->getContext());
    }
}
