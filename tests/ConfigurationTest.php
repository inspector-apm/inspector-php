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

    public function testDefault()
    {
        $configuration = new Configuration('aaa');
        $this->assertSame('aaa', $configuration->getApiKey());

        $this->assertSame('https://ingest.inspector.dev', $configuration->getUrl());
        $this->assertSame([], $configuration->getOptions());
        $this->assertSame('sync', $configuration->getTransport());
        $this->assertSame(true, $configuration->isEnabled());
    }

    public function testDisable()
    {
        $configuration = new Configuration();

        $this->assertFalse($configuration->isEnabled());
    }

    public function testFluentApi()
    {
        $configuration = new Configuration('aaa');

        $this->assertInstanceOf(Configuration::class, $configuration->setApiKey('xxx'));
        $this->assertInstanceOf(Configuration::class, $configuration->setUrl('http://www.example.com'));
        $this->assertInstanceOf(Configuration::class, $configuration->setOptions([]));
        $this->assertInstanceOf(Configuration::class, $configuration->setEnabled(true));
        $this->assertInstanceOf(Configuration::class, $configuration->setTransport('async'));
    }
}
