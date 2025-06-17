<?php

namespace Inspector\Tests;

use Inspector\Configuration;
use Inspector\Transports\CurlTransport;
use PHPUnit\Framework\TestCase;

class TransportTest extends TestCase
{
    public function testDefaultMaxItems()
    {
        $transport = new CurlTransport(new Configuration('foo'));

        $transport->addEntry(['model' => 'example']);

        $this->assertCount(1, $transport->getQueue());

        for ($i = 0; $i < 150; $i++) {
            $transport->addEntry(['model' => 'example']);
        }

        // A transaction + 100 segments
        $this->assertCount(101, $transport->getQueue());
    }

    public function testIncreaseMaxItems()
    {
        $transport = new CurlTransport(
            (new Configuration('foo'))->setMaxItems(150)
        );

        for ($i = 0; $i < 150; $i++) {
            $transport->addEntry(['model' => 'example']);
        }

        $this->assertCount(150, $transport->getQueue());
    }
}
