<?php

namespace LogEngine\Tests;


use LogEngine\LogEngine;
use PHPUnit\Framework\TestCase;

class LogEngineTest extends TestCase
{
    public function testLogEngineInstance()
    {
        $this->assertInstanceOf(LogEngine::class, new LogEngine);
    }
}