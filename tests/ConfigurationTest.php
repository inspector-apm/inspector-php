<?php

namespace Inspector\Tests;


use Inspector\Configuration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Configuration('');
    }

    public function testCreateInstance()
    {
        $initialUrl = 'http://www.example.com/api';
        $initialApiKey = 'aaa';

        $configuration = new Configuration($initialApiKey);
        $configuration->setUrl($initialUrl);

        $this->assertSame($initialUrl, $configuration->getUrl());
        $this->assertSame($initialApiKey, $configuration->getApiKey());
    }
}