<?php

namespace Compwright\PsrCors;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class CorsTest extends TestCase
{
    private Cors $cors;

    protected function setUp(): void
    {
        $this->cors = new Cors();
    }

    #[TestWith(['GET', '/'], 'get_no_header')]
    #[TestWith(['GET', '/', ['Access-Control-Request-Method' => 'POST']], 'get_with_header')]
    #[TestWith(['OPTIONS', '/'], 'options_no_header')]
    public function testNotPreflightRequest(mixed ...$args): void
    {
        $this->assertFalse($this->cors->isPreflightRequest(new ServerRequest(...$args)));
    }

    #[TestWith(['OPTIONS', '/', ['Access-Control-Request-Method' => 'POST']])]
    public function testIsPreflightRequest(mixed ...$args): void
    {
        $this->assertTrue($this->cors->isPreflightRequest(new ServerRequest(...$args)));
    }
}
