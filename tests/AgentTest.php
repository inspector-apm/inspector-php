<?php

namespace LogEngine\Tests;


use LogEngine\ApmAgent;
use LogEngine\Configuration;
use PHPUnit\Framework\TestCase;

class AgentTest extends TestCase
{
    /**
     * @throws \LogEngine\Exceptions\LogEngineApmException
     */
    public function testLogEngineInstance()
    {
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false);

        $this->assertInstanceOf(ApmAgent::class, new ApmAgent($configuration));
    }
}