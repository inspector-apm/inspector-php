<?php

namespace Inspector\Tests;


use Inspector\ApmAgent;
use Inspector\Configuration;
use PHPUnit\Framework\TestCase;

class AgentTest extends TestCase
{
    /**
     * @throws \Inspector\Exceptions\LogEngineApmException
     */
    public function testLogEngineInstance()
    {
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false);

        $this->assertInstanceOf(ApmAgent::class, new ApmAgent($configuration));
    }
}