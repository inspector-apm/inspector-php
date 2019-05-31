<?php


namespace Inspector\Tests;


use Inspector\ApmAgent;
use Inspector\Configuration;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    /**
     * @var ApmAgent
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
        $this->apm = new ApmAgent($configuration);
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
        $span = $this->apm->startSpan(__FUNCTION__);

        $this->assertArraySubset([
            'model' => 'span',
            'type' => __FUNCTION__,
            'transaction' => $this->apm->currentTransaction()->getHash(),
            'context' => [],
        ], $span->jsonSerialize());
    }
}