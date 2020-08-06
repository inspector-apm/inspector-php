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
        $this->assertSame(100, $configuration->getMaxItems());
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
        $this->assertSame('xxx', $configuration->getApiKey());

        $this->assertInstanceOf(Configuration::class, $configuration->setUrl('http://www.example.com'));
        $this->assertSame('http://www.example.com', $configuration->getUrl());

        $this->assertInstanceOf(Configuration::class, $configuration->setOptions([]));
        $this->assertSame([], $configuration->getOptions());

        $this->assertInstanceOf(Configuration::class, $configuration->setEnabled(true));
        $this->assertSame(true, $configuration->isEnabled());

        $this->assertInstanceOf(Configuration::class, $configuration->setTransport('async'));
        $this->assertSame('async', $configuration->getTransport());

        $this->assertInstanceOf(Configuration::class, $configuration->setMaxItems(150));
        $this->assertSame(150, $configuration->getMaxItems());
    }
}
