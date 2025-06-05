<?php

namespace Inspector\Tests;


use Inspector\Configuration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testDefault()
    {
        $configuration = new Configuration('aaa');
        $this->assertSame('aaa', $configuration->getIngestionKey());

        $this->assertSame('https://ingest.inspector.dev', $configuration->getUrl());
        $this->assertSame([], $configuration->getOptions());
        $this->assertSame('async', $configuration->getTransport());
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

        $this->assertSame($configuration, $configuration->setUrl('http://www.example.com'));
        $this->assertSame('http://www.example.com', $configuration->getUrl());

        $this->assertSame($configuration, $configuration->setIngestionKey('xxx'));
        $this->assertSame('xxx', $configuration->getIngestionKey());

        $this->assertSame($configuration, $configuration->setEnabled(true));
        $this->assertSame(true, $configuration->isEnabled());

        $this->assertSame($configuration, $configuration->setMaxItems(150));
        $this->assertSame(150, $configuration->getMaxItems());

        $this->assertSame($configuration, $configuration->setTransport('sync'));
        $this->assertSame('sync', $configuration->getTransport());

        $this->assertSame($configuration, $configuration->addOption('one', 1));
        $this->assertSame(['one' => 1], $configuration->getOptions());
        $this->assertSame($configuration, $configuration->addOption('two', 2));
        $this->assertSame(['one' => 1, 'two' => 2], $configuration->getOptions());
        // It override existing keys.
        $this->assertSame($configuration, $configuration->addOption('one', 'number1'));
        $this->assertSame(['one' => 'number1', 'two' => 2], $configuration->getOptions());

        $this->assertSame($configuration, $configuration->setOptions([]));
        $this->assertSame([], $configuration->getOptions());
    }

    public function testUrlCanNotBeEmpty()
    {
        $configuration = new Configuration('aaa');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL can not be empty');

        $configuration->setUrl('');
    }

    public function testUrlMustBeValid()
    {
        $configuration = new Configuration();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL is invalid');

        $configuration->setUrl('foobar');
    }

    public function testIngestionKeyCanNotBeEmpty()
    {
        $configuration = new Configuration();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ingestion key cannot be empty');

        $configuration->setIngestionKey('');
    }
}
