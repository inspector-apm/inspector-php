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
    public function testLogEngineInstance()
    {
        $configuration = new Configuration('example-key');
        $configuration->setEnabled(false);

        $this->assertInstanceOf(Inspector::class, new Inspector($configuration));
    }
}