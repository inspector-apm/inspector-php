<?php


namespace Inspector\Tests;


use Inspector\Models\Partials\Host;
use Inspector\Models\Partials\User;
use PHPUnit\Framework\TestCase;

class PartialsTest extends TestCase
{
    public function testHost()
    {
        $host = new Host();
        $this->assertEquals(gethostname(), $host->hostname);
        $this->assertEquals(gethostbyname(gethostname()), $host->ip);

        if (PHP_OS !== 'Linux') {
            $this->assertEquals(0, $host->cpu_usage);
            $this->assertEquals(0, $host->memory_usage);
        }
    }

    public function testUser()
    {
        $user = new User(1, 'Valerio');
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Valerio', $user->name);
        $this->assertNull($user->email);
    }
}
