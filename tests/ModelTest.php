<?php


namespace Inspector\Tests;


use Inspector\Inspector;
use Inspector\Configuration;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
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
    public function setUp(): void
    {
        $configuration = new Configuration('example-api-key');
        $configuration->setEnabled(false);

        $this->inspector = new Inspector($configuration);
        $this->inspector->startTransaction('testcase');
    }

    public function testTransactionData()
    {
        $this->assertSame($this->inspector->currentTransaction()::MODEL_NAME, $this->inspector->currentTransaction()->model);
        $this->assertSame($this->inspector->currentTransaction()::TYPE_PROCESS, $this->inspector->currentTransaction()->type);
        $this->assertSame('testcase', $this->inspector->currentTransaction()->name);
    }

    public function testSegmentData()
    {
        $segment = $this->inspector->startSegment(__FUNCTION__, 'hello segment!');

        $this->assertIsArray($segment->toArray());
        $this->assertSame($segment::MODEL_NAME, $segment->model);
        $this->assertSame(__FUNCTION__, $segment->type);
        $this->assertSame('hello segment!', $segment->label);
        $this->assertSame($this->inspector->currentTransaction()->only(['hash', 'timestamp']), $segment->transaction);
    }

    public function testErrorData()
    {
        $error = $this->inspector->reportException(new \Exception('test error'));
        $error_arr = $error->toArray();

        $this->assertArrayHasKey('message', $error_arr);
        $this->assertArrayHasKey('stack', $error_arr);
        $this->assertArrayHasKey('file', $error_arr);
        $this->assertArrayHasKey('line', $error_arr);
        $this->assertArrayHasKey('code', $error_arr);
        $this->assertArrayHasKey('class', $error_arr);
        $this->assertArrayHasKey('timestamp', $error_arr);

        $this->assertSame($error::MODEL_NAME, $error->model);
        $this->assertSame($this->inspector->currentTransaction()->only(['hash']), $error->transaction);
    }

    public function testSetContext()
    {
        $this->inspector->currentTransaction()->addContext('test', ['foo' => 'bar']);

        $this->assertEquals(['test' => ['foo' => 'bar']], $this->inspector->currentTransaction()->context);
    }

    public function testEncoding()
    {
        $this->assertStringContainsString(trim(json_encode([
            'model' => 'transaction',
        ]), '{}'), json_encode($this->inspector->currentTransaction()));

        $this->assertStringContainsString(trim(json_encode([
            'model' => 'segment',
            'type' => 'test',
        ]), '{}'), json_encode($this->inspector->startSegment('test')));

        $error = $this->inspector->reportException(new \DomainException('test error'));
        $this->assertStringContainsString(trim(json_encode([
            'model' => 'error'
        ]), '{}'), json_encode($error));
    }
}
