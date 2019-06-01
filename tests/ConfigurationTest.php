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
        $this->assertSame($initialApiKey, $configuration->getApiKey());

        $configuration->setUrl($initialUrl);
        $this->assertSame($initialUrl, $configuration->getUrl());

        $this->assertSame(true, $configuration->isEnabled());

        $this->assertSame([], $configuration->getOptions());
    }

    public function testFluentApi()
    {
        $configuration = new Configuration('aaa');

        $this->assertInstanceOf(Configuration::class, $configuration->setApiKey('xxx'));
        $this->assertInstanceOf(Configuration::class, $configuration->setUrl('http://www.example.com'));
        $this->assertInstanceOf(Configuration::class, $configuration->setOptions([]));
        $this->assertInstanceOf(Configuration::class, $configuration->setEnabled(true));
    }
}