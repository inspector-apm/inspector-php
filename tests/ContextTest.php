<?php


namespace Inspector\Tests;


use Inspector\Inspector;
use Inspector\Configuration;
use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{
    /**
     * @var Inspector
     */
    protected $apm;

    /**
     * @throws \Inspector\Exceptions\LogEngineApmException
     */
    public function setUp()
    {
        $config = new Configuration('example-key');
        $config->setEnabled(false);

        $this->apm = new Inspector($config);
        $this->apm->startTransaction('testcase');
    }

    /**
     * @throws \Exception
     */
    public function testTransactionContextEmpty()
    {
        $this->assertSame(json_encode([]), json_encode($this->apm->currentTransaction()->getContext()));
    }

    /**
     * @throws \Exception
     */
    public function testSpanContextEmpty()
    {
        $span = $this->apm->startSpan('testSpanContextEmpty');

        $this->assertSame(json_encode([]), json_encode($span->getContext()));
    }

    /**
     * @throws \Exception
     */
    public function testErrorContextEmpty()
    {
        $error = $this->apm->reportException(new \Exception('testSpanContextEmpty'));

        $this->assertSame(json_encode([]), json_encode($error->getContext()));
    }
}