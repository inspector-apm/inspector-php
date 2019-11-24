<?php

namespace Inspector\Tests;


use Inspector\Inspector;
use Inspector\Configuration;
use PHPUnit\Framework\TestCase;

class AgentTest extends TestCase
{
    /**
     * @throws \Inspector\Exceptions\InspectorException
     */
    public function testInspectorInstance()
    {
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false);

        $this->assertInstanceOf(Inspector::class, new Inspector($configuration));
    }

    public function testAddEntry()
    {
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false);

        $inspector = new Inspector($configuration);
        $inspector->startTransaction('ttransaction-test');

        $this->assertInstanceOf(
            Inspector::class,
            $inspector->addEntries($inspector->startSegment('span-test'))
        );

        $this->assertInstanceOf(
            Inspector::class,
            $inspector->addEntries([$inspector->startSegment('span-test')])
        );
    }

    public function testTransport()
    {
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false)
        ->setTransport('async');

        $this->assertEquals('async', $configuration->getTransport());
    }

    public function testCallbackThrow()
    {
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false);

        $inspector = new Inspector($configuration);
        $inspector->startTransaction('ttransaction-test');

        $this->expectException(\Exception::class);

        $inspector->addSegment(function () {
            throw new \Exception();
        }, 'callback', 'test callback', true);
    }
}
