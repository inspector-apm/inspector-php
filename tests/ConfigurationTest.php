<?php

namespace LogEngine\Tests;


use LogEngine\Transport\Configuration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Configuration('', '');
    }

    public function testCreateInstance()
    {
        $initialUrl = 'http://www.example.com/api';
        $initialApiKey = 'aaa';

        $configuration = new Configuration($initialUrl, $initialApiKey);

        $this->assertSame($initialUrl, $configuration->getUrl());
        $this->assertSame($initialApiKey, $configuration->getApiKey());
    }
}