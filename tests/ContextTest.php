<?php


namespace LogEngine\Tests;


use LogEngine\ApmAgent;
use LogEngine\Configuration;
use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{
    /**
     * @var ApmAgent
     */
    protected $apm;

    /**
     * @throws \LogEngine\Exceptions\LogEngineApmException
     */
    public function setUp()
    {
        $config = new Configuration('example-key');
        $config->setEnabled(false);

        $this->apm = new ApmAgent($config);
    }

    /**
     * @throws \Exception
     */
    public function testTransactionContextEmpty()
    {
        $transaction = $this->apm->startTransaction('testcase');

        $this->assertSame([], $transaction->getContext()->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testSpanContextEmpty()
    {
        $this->apm->startTransaction('testcase');
        $span = $this->apm->startSpan('testSpanContextEmpty');

        $this->assertSame([], $span->getContext()->toArray());
    }
}