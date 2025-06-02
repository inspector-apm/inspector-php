<?php

namespace Inspector\Tests;


use Inspector\Inspector;
use Inspector\Configuration;
use Inspector\Models\Arrayable;
use Inspector\Transports\AsyncTransport;
use Inspector\Transports\CurlTransport;
use PHPUnit\Framework\TestCase;

class InspectorTest extends TestCase
{
    public function testItCreatesAsyncTransportByDefault()
    {
        $configuration = new Configuration('example-api-key');

        $inspector = new Inspector($configuration);
        $property = new \ReflectionProperty(Inspector::class, 'transport');
        $property->setAccessible(true);
        $this->assertInstanceOf(AsyncTransport::class, $property->getValue($inspector));
    }

    public function testItCreatesCurlTransport()
    {
        $configuration = new Configuration('example-api-key');
        $configuration->setTransport('sync');

        $inspector = new Inspector($configuration);
        $property = new \ReflectionProperty(Inspector::class, 'transport');
        $property->setAccessible(true);
        $this->assertInstanceOf(CurlTransport::class, $property->getValue($inspector));
    }

    public function testSetTransportAcceptsTransportImplementation()
    {
        $configuration = new Configuration('example-api-key');

        $inspector = new Inspector($configuration);
        $inspector->setTransport($transport = new class implements \Inspector\Transports\TransportInterface {
            public function addEntry(Arrayable $entry)
            {
                // Custom addEntry logic
            }

            public function flush()
            {
                // Custom flush logic
            }
        });

        $property = new \ReflectionProperty(Inspector::class, 'transport');
        $property->setAccessible(true);
        $this->assertSame($transport, $property->getValue($inspector));
    }

    public function testSetTransportAcceptsTransportCallable()
    {
        $configuration = new Configuration('example-api-key');
        $inspector = new Inspector($configuration);

        // The configuration instance is passed to the callable.
        $inspector->setTransport(function (Configuration $configuration) {
            return new TestingTransport($configuration);
        });

        $property = new \ReflectionProperty(Inspector::class, 'transport');
        $property->setAccessible(true);

        $this->assertInstanceOf(TestingTransport::class, $property->getValue($inspector));
        $this->assertSame($configuration, $property->getValue($inspector)->config);
    }

    public function testSetTransportFailed()
    {
        $this->expectException(\Inspector\Exceptions\InspectorException::class);
        $this->expectExceptionMessage('Invalid transport resolver.');

        $configuration = new Configuration('example-api-key');
        $inspector = new Inspector($configuration);

        $inspector->setTransport('invalid-resolver');
    }

    public function testItCallsTransportResetQueue()
    {
        $configuration = new Configuration('example-api-key');
        $inspector = new Inspector($configuration);
        $inspector->setTransport(new TestingTransport($configuration));

        $_SERVER['TestingTransport::resetQueue'] = false;

        $inspector->reset();

        $this->assertTrue($_SERVER['TestingTransport::resetQueue']);

        unset($_SERVER['TestingTransport::resetQueue']);
    }
}

class TestingTransport implements \Inspector\Transports\TransportInterface
{
    public function __construct(public Configuration $config)
    {
        // Custom transport initialization logic
    }

    public function addEntry(Arrayable $entry)
    {
        // Stub implementation
    }

    public function flush()
    {
        // Stub implementation
    }

    public function resetQueue()
    {
        $_SERVER['TestingTransport::resetQueue'] = true;
    }
}
