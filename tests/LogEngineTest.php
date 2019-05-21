<?php

namespace LogEngine\Tests;


use LogEngine\LogEngineAgent;
use PHPUnit\Framework\TestCase;

class LogEngineTest extends TestCase
{
    public function testLogEngineInstance()
    {
        $this->assertInstanceOf(LogEngineAgent::class, new LogEngineAgent);
    }
}