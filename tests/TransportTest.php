<?php

declare(strict_types=1);

namespace Inspector\Tests;

use Inspector\Configuration;
use Inspector\Models\Transaction;
use Inspector\Transports\CurlTransport;
use PHPUnit\Framework\TestCase;

class TransportTest extends TestCase
{
    public function testDefaultMaxItems(): void
    {
        $transport = new CurlTransport(new Configuration('foo'));

        $transaction = new Transaction('test');

        $transport->addEntry($transaction);

        $this->assertCount(1, $transport->getQueue());

        for ($i = 0; $i < 200; $i++) {
            $transport->addEntry($transaction);
        }

        // A transaction + 100 segments
        $this->assertCount(151, $transport->getQueue());
    }

    public function testIncreaseMaxItems(): void
    {
        $transport = new CurlTransport(
            (new Configuration('foo'))->setMaxItems(151)
        );

        $transaction = new Transaction('test');

        for ($i = 0; $i < 200; $i++) {
            $transport->addEntry($transaction);
        }

        $this->assertCount(151, $transport->getQueue());
    }
}
