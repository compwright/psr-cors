<?php

namespace Compwright\PsrCors;

use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Middleware implements MiddlewareInterface
{
    private Cors $cors;

    private ResponseFactoryInterface $responseFactory;

    /** @var non-empty-array<string> */
    private array $allowedOrigins = ['*'];

    /** @var non-empty-array<string> */
    private array $allowedMethods = ['*'];

    /** @var non-empty-array<string> */
    private array $allowedHeaders = ['*'];

    /** @var null|non-empty-array<string> */
    private ?array $exposedHeaders = null;

    private ?int $maxAge = null;

    private bool $supportsCredentials = false;

    private function __construct(Cors $cors, ResponseFactoryInterface $responseFactory)
    {
        $this->cors = $cors;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param true|non-empty-array<string> $allowedOrigins
     * @param true|non-empty-array<string> $allowedMethods
     * @param true|non-empty-array<string> $allowedHeaders
     * @param null|non-empty-array<string> $exposedHeaders
     */
    public static function create(
        ResponseFactoryInterface $responseFactory,
        true|array $allowedOrigins = true,
        true|array $allowedMethods = true,
        true|array $allowedHeaders = true,
        bool $supportsCredentials = false,
        ?int $maxAge = null,
        ?array $exposedHeaders = null
    ): self {
        return (new self(new Cors(), $responseFactory))
            ->allowOrigins($allowedOrigins === true ? ['*'] : $allowedOrigins)
            ->allowMethods($allowedMethods === true ? ['*'] : $allowedMethods)
            ->allowHeaders($allowedHeaders === true ? ['*'] : $allowedHeaders)
            ->supportCredentials($supportsCredentials)
            ->exposeHeaders($exposedHeaders)
            ->maxAge($maxAge);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->cors->isPreflightRequest($request)) {
            return $this->cors->handlePreflight(
                $this->responseFactory->createResponse(),
                $request,
                $this->allowedOrigins ?? ['*'],
                $this->allowedMethods ?? ['*'],
                $this->allowedHeaders ?? ['*'],
                $this->supportsCredentials ?? false,
                $this->maxAge ?? null
            );
        }

        $response = $handler->handle($request);

        return $this->cors->handlePostflight(
            $response,
            $request,
            $this->allowedOrigins ?? ['*'],
            $this->supportsCredentials ?? false,
            $this->exposedHeaders ?? []
        );
    }

    /**
     * @param non-empty-array<string> $origins
     */
    private function allowOrigins(array $origins): self
    {
        if (count($origins) < 1) {
            throw new InvalidArgumentException('At least one origin is required');
        }

        if (in_array('*', $origins)) {
            $this->allowedOrigins = ['*'];
            return $this;
        }

        $this->allowedOrigins = $origins;
        return $this;
    }

    /**
     * @param non-empty-array<string> $methods
     */
    private function allowMethods(array $methods): self
    {
        if (count($methods) < 1) {
            throw new InvalidArgumentException('At least one method is required');
        }
        $this->allowedMethods = $methods;
        return $this;
    }

    /**
     * @param non-empty-array<string> $headers
     */
    private function allowHeaders(array $headers): self
    {
        if (count($headers) < 1) {
            throw new InvalidArgumentException('At least one header is required');
        }
        $this->allowedHeaders = $headers;
        return $this;
    }

    private function supportCredentials(bool $supportsCredentials): self
    {
        $this->supportsCredentials = $supportsCredentials;
        return $this;
    }

    /**
     * @param null|non-empty-array<string> $headers
     */
    private function exposeHeaders(?array $headers): self
    {
        $this->exposedHeaders = $headers;
        return $this;
    }

    private function maxAge(?int $maxAge): self
    {
        $this->maxAge = $maxAge;
        return $this;
    }
}
