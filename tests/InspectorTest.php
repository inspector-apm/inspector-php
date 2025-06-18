<?php

namespace Inspector\Tests;

use Inspector\Inspector;
use Inspector\Configuration;
use Inspector\Models\Model;
use Inspector\Transports\AsyncTransport;
use Inspector\Transports\CurlTransport;
use Inspector\Transports\TransportInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

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
        $inspector->setTransport($transport = new class () implements TransportInterface {
            public function addEntry(Model $model): TransportInterface
            {
                // Custom addEntry logic
                return $this;
            }

            public function resetQueue(): TransportInterface
            {
                // Custom logic
                return $this;
            }

            public function flush(): TransportInterface
            {
                // Custom flush logic
                return $this;
            }
        });

        $property = new \ReflectionProperty(Inspector::class, 'transport');
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

        $this->assertInstanceOf(TestingTransport::class, $property->getValue($inspector));
        $this->assertSame($configuration, $property->getValue($inspector)->config);
    }

    public function testItCallsTransportResetQueue()
    {
        $configuration = new Configuration('example-api-key');
        $inspector = new Inspector($configuration);
        $inspector->setTransport(new TestingTransport($configuration));

        $_SERVER['TestingTransport::resetQueue'] = false;

        $inspector->reset();

        // @phpstan-ignore-next-line
        $this->assertTrue($_SERVER['TestingTransport::resetQueue']);

        unset($_SERVER['TestingTransport::resetQueue']);
    }

    public function testCreateMethodWithoutConfigureCallback()
    {
        $inspector = Inspector::create('example-api-key');

        $reflector = new ReflectionProperty($inspector, 'configuration');
        $reflector->setAccessible(true);

        $configuration = $reflector->getValue($inspector);

        $this->assertInstanceOf(Configuration::class, $configuration);

        $this->assertSame('example-api-key', $configuration->getIngestionKey());
    }

    public function testCreateMethodWithConfigureCallback()
    {
        $inspector = Inspector::create('example-api-key', function (Configuration $config) {
            $config
                ->setUrl('https://ingest.example.com')
                ->setMaxItems(111);
        });

        $reflector = new ReflectionProperty($inspector, 'configuration');
        $reflector->setAccessible(true);

        $configuration = $reflector->getValue($inspector);

        $this->assertSame('https://ingest.example.com', $configuration->getUrl());
        $this->assertSame(111, $configuration->getMaxItems());
    }

    public function testConfigureMethod()
    {
        $inspector = Inspector::create('example-api-key');

        $inspector->configure(function (Configuration $config) {
            $config->setIngestionKey('change-api-key');
        });

        $reflector = new ReflectionProperty($inspector, 'configuration');
        $reflector->setAccessible(true);

        $configuration = $reflector->getValue($inspector);

        $this->assertSame('change-api-key', $configuration->getIngestionKey());
    }
}

class TestingTransport implements TransportInterface
{
    public function __construct(public Configuration $config)
    {
        // Custom transport initialization logic
    }

    public function addEntry(Model $model): TransportInterface
    {
        // Stub implementation
        return $this;
    }

    public function flush(): TransportInterface
    {
        // Stub implementation
        return $this;
    }

    public function resetQueue(): TransportInterface
    {
        $_SERVER['TestingTransport::resetQueue'] = true;
        return $this;
    }
}
