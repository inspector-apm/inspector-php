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
    public $apm;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @throws \Exception
     */
    public function setUp()
    {
        $configuration = new Configuration('example-api-key');
        $configuration->setEnabled(false);

        $this->apm = new Inspector($configuration);
        $this->apm->startTransaction('testcase');
    }

    public function testTransactionData()
    {
        $this->assertArraySubset([
            'model' => 'transaction',
            'type' => $this->apm->currentTransaction()::TYPE_PROCESS,
            'name' => 'testcase',
        ], $this->apm->currentTransaction()->toArray(), true);
    }

    public function testSegmentData()
    {
        $segment = $this->apm->startSegment(__FUNCTION__, 'hello segment!');

        $this->assertArraySubset([
            'model' => 'segment',
            'type' => __FUNCTION__,
            'label' => 'hello segment!',
            'transaction' => $this->apm->currentTransaction()->only(['hash', 'timestamp']),
        ], $segment->toArray());
    }

    public function testErrorData()
    {
        $error = $this->apm->reportException(
            new \Exception('test error')
        )->toArray();

        $this->assertArrayHasKey('message', $error);
        $this->assertArrayHasKey('stack', $error);
        $this->assertArrayHasKey('file', $error);
        $this->assertArrayHasKey('line', $error);
        $this->assertArrayHasKey('code', $error);
        $this->assertArrayHasKey('class', $error);
        $this->assertArrayHasKey('timestamp', $error);

        $this->assertArraySubset([
            'model' => 'error',
            'transaction' => $this->apm->currentTransaction()->only(['hash']),
        ], $error);
    }

    public function testSetContext()
    {
        $this->apm->currentTransaction()->addContext('test', ['foo' => 'bar']);

        $this->assertEquals(['test' => ['foo' => 'bar']], $this->apm->currentTransaction()->context);
    }

    public function testEncoding()
    {
        $this->assertContains(trim(json_encode([
            'model' => 'transaction',
        ]), '{}'), json_encode($this->apm->currentTransaction()));

        $this->assertContains(trim(json_encode([
            'model' => 'segment',
            'type' => 'test',
        ]), '{}'), json_encode($this->apm->startSegment('test')));

        $error = $this->apm->reportException(new \DomainException('test error'));
        $this->assertContains(trim(json_encode([
            'model' => 'error'
        ]), '{}'), json_encode($error));
    }
}
