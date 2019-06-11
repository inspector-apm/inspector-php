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
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false);
        $this->apm = new Inspector($configuration);
        $this->apm->startTransaction('testcase');
    }

    public function testTransactionModelSerialization()
    {
        $this->assertArraySubset([
            'model' => 'transaction',
            'type' => $this->apm->currentTransaction()::TYPE_PROCESS,
            'name' => 'testcase',
            'result' => null,
            'context' => [],
        ], $this->apm->currentTransaction()->jsonSerialize());
    }

    public function testSpanModelSerialization()
    {
        $span = $this->apm->startSpan(__FUNCTION__)
            ->setMessage('hello span!');

        $this->assertArraySubset([
            'model' => 'span',
            'type' => __FUNCTION__,
            'message' => 'hello span!',
            'transaction' => $this->apm->currentTransaction()->getHash(),
            'context' => [],
        ], $span->jsonSerialize());
    }

    public function testErrorModelSerialization()
    {
        $exception = new \Exception('test error');
        $error = $this->apm->reportException($exception);

        $error = $error->jsonSerialize();

        $this->assertArrayHasKey('message', $error);
        $this->assertArrayHasKey('stack', $error);
        $this->assertArrayHasKey('file', $error);
        $this->assertArrayHasKey('line', $error);
        $this->assertArrayHasKey('code', $error);
        $this->assertArrayHasKey('class', $error);
        $this->assertArrayHasKey('duration', $error);
        $this->assertArrayHasKey('timestamp', $error);

        $this->assertArraySubset([
            'model' => 'error',
            'transaction' => $this->apm->currentTransaction()->getHash(),
            'context' => [],
            'group_hash' => md5(get_class($exception).$exception->getFile().$exception->getLine()),
        ], $error);
    }
}