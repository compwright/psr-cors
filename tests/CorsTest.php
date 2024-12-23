<?php

namespace Compwright\PsrCors;

use Generator;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class CorsTest extends TestCase
{
    private Cors $cors;

    protected function setUp(): void
    {
        $this->cors = new Cors();
    }

    public function provideRequests(): Generator
    {
        yield 'get_no_header' => [
            new ServerRequest('GET', '/'),
            false
        ];

        yield 'get_with_header' => [
            new ServerRequest('GET', '/', ['Access-Control-Request-Method' => 'POST']),
            false
        ];

        yield 'options_no_header' => [
            new ServerRequest('OPTIONS', '/'),
            false
        ];

        yield 'options_with_header' => [
            new ServerRequest('OPTIONS', '/', ['Access-Control-Request-Method' => 'POST']),
            true
        ];
    }

    /**
     * @dataProvider provideRequests
     */
    public function testIsPreflightRequest(ServerRequestInterface $request, bool $expected): void
    {
        $this->assertEquals($expected, $this->cors->isPreflightRequest($request));
    }
}
