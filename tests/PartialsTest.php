<?php

declare(strict_types=1);

namespace Inspector\Tests;

use Inspector\Models\Partials\Host;
use Inspector\Models\Partials\Http;
use Inspector\Models\Partials\Request;
use Inspector\Models\Partials\Socket;
use Inspector\Models\Partials\Url;
use Inspector\Models\Partials\User;
use PHPUnit\Framework\TestCase;

use function gethostbyname;
use function gethostname;

use const PHP_OS_FAMILY;

class PartialsTest extends TestCase
{
    public function testHost()
    {
        $host = new Host();
        $this->assertEquals(gethostname(), $host->hostname);
        $this->assertEquals(gethostbyname(gethostname()), $host->ip);

        if (PHP_OS_FAMILY !== 'Linux') {
            $this->assertEquals(0, $host->cpu);
            $this->assertEquals(0, $host->ram);
        }

        $this->assertSame(PHP_OS_FAMILY, $host->os);
    }

    public function testHttp()
    {
        $http = new Http();

        $this->assertInstanceOf(Request::class, $http->request);
        $this->assertInstanceOf(Url::class, $http->url);
    }

    public function testRequestMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request();
        $this->assertSame('GET', $request->method);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = new Request();
        $this->assertSame('POST', $request->method);
    }

    public function testRequestVersion()
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $request = new Request();
        $this->assertSame('/1.1', $request->version);

        unset($_SERVER['SERVER_PROTOCOL']);
        $request = new Request();
        $this->assertSame('unknown', $request->version);
    }

    public function testRequestSocket()
    {
        $request = new Request();
        $this->assertInstanceOf(Socket::class, $request->socket);
    }

    public function testSocketRemoteAddress()
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.33.11';
        $socket = new Socket();
        $this->assertSame('192.168.33.11', $socket->remote_address);

        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $socket = new Socket();
        $this->assertSame('127.0.0.1', $socket->remote_address);

        unset($_SERVER['REMOTE_ADDR']);
        $socket = new Socket();
        $this->assertSame('', $socket->remote_address);
    }

    public function testSocketEncrypted()
    {
        $_SERVER['HTTPS'] = 'on';
        $socket = new Socket();
        $this->assertTrue($socket->encrypted);

        unset($_SERVER['HTTPS']);
        $socket = new Socket();
        $this->assertFalse($socket->encrypted);
    }

    public function testUrlProtocol()
    {
        $_SERVER['HTTPS'] = 'on';
        $url = new Url();
        $this->assertSame('https', $url->protocol);

        unset($_SERVER['HTTPS']);
        $url = new Url();
        $this->assertSame('http', $url->protocol);
    }

    public function testUrlPort()
    {
        $_SERVER['SERVER_PORT'] = 8000;
        $url = new Url();
        $this->assertSame('8000', $url->port);

        unset($_SERVER['SERVER_PORT']);
        $url = new Url();
        $this->assertSame('', $url->port);
    }

    public function testUrlPath()
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $url = new Url();
        $this->assertSame('/index.php', $url->path);

        $_SERVER['SCRIPT_NAME'] = '/main.php';
        $url = new Url();
        $this->assertSame('/main.php', $url->path);

        unset($_SERVER['SCRIPT_NAME']);
        $url = new Url();
        $this->assertSame('', $url->path);
    }

    public function testUrlSearch()
    {
        $_SERVER['QUERY_STRING'] = 'name=inspector&language=php';
        $url = new Url();
        $this->assertSame('?name=inspector&language=php', $url->search);

        unset($_SERVER['QUERY_STRING']);
        $url = new Url();
        $this->assertSame('?', $url->search);
    }

    public function testUrlFull()
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost:8000';
        $url = new Url();
        $this->assertSame('https://localhost:8000/', $url->full);

        $_SERVER['HTTPS'] = 'on';
        $_SERVER['REQUEST_URI'] = '/api/users';
        $_SERVER['HTTP_HOST'] = 'localhost:8000';
        $url = new Url();
        $this->assertSame('https://localhost:8000/api/users', $url->full);
    }

    public function testUser()
    {
        $user = new User(1, 'Valerio');
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Valerio', $user->name);
        $this->assertNull($user->email);

        $user = new User(1, 'Valerio', 'valerio@inspector.dev');
        $this->assertSame('valerio@inspector.dev', $user->email);
    }
}
