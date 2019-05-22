<?php

namespace LogEngine\Tests;


use LogEngine\ApmAgent;
use LogEngine\Configuration;
use PHPUnit\Framework\TestCase;

class LogEngineTest extends TestCase
{
    /**
     * @throws \LogEngine\Exceptions\LogEngineApmException
     */
    public function testLogEngineInstance()
    {
        $configuration = new Configuration('key');

        $this->assertInstanceOf(ApmAgent::class, new ApmAgent($configuration));
    }
}