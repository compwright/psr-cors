<?php

namespace Compwright\PsrCors;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Cors
{
    public function isPreflightRequest(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === 'OPTIONS'
            && $request->hasHeader('Access-Control-Request-Method');
    }

    /**
     * @param non-empty-array<string> $allowedOrigins
     * @param non-empty-array<string> $allowedMethods
     * @param non-empty-array<string> $allowedHeaders
     */
    public function handlePreflight(
        ResponseInterface $response,
        ServerRequestInterface $request,
        array $allowedOrigins,
        array $allowedMethods,
        array $allowedHeaders,
        bool $supportsCredentials,
        ?int $maxAge
    ): ResponseInterface {
        $response = $response->withStatus(204);

        $response = $this->configureAllowedOrigin(
            $response,
            $request,
            $allowedOrigins,
            $supportsCredentials
        );

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            if ($supportsCredentials) {
                $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
            }

            if ($allowedMethods === ['*']) {
                $response = $this->varyHeader($response, 'Access-Control-Request-Method')
                    ->withHeader(
                        'Access-Control-Allow-Methods',
                        strtoupper($request->getHeaderLine('Access-Control-Request-Method'))
                    );
            } elseif (count($allowedMethods) > 0) {
                $response = $response->withHeader('Access-Control-Allow-Methods', $allowedMethods);
            }

            if ($allowedHeaders === ['*']) {
                $response = $this->varyHeader($response, 'Access-Control-Request-Headers')
                    ->withHeader(
                        'Access-Control-Allow-Headers',
                        $request->getHeaderLine('Access-Control-Request-Headers')
                    );
            } elseif (count($allowedHeaders) > 0) {
                $response = $response->withHeader('Access-Control-Allow-Headers', $allowedHeaders);
            }

            if (!is_null($maxAge)) {
                $response = $response->withHeader('Access-Control-Max-Age', (string) $maxAge);
            }
        }

        return $this->varyHeader($response, 'Access-Control-Request-Method');
    }

    /**
     * @param non-empty-array<string> $allowedOrigins
     * @param array<string> $exposedHeaders
     */
    public function handlePostflight(
        ResponseInterface $response,
        ServerRequestInterface $request,
        array $allowedOrigins,
        bool $supportsCredentials,
        array $exposedHeaders
    ): ResponseInterface {
        if ($request->getMethod() === 'OPTIONS') {
            $response = $this->varyHeader($response, 'Access-Control-Request-Method');
        }

        $response = $this->configureAllowedOrigin(
            $response,
            $request,
            $allowedOrigins,
            $supportsCredentials
        );

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            if ($supportsCredentials) {
                $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
            }
            if (count($exposedHeaders) > 0) {
                $response = $response->withHeader('Access-Control-Expose-Headers', $exposedHeaders);
            }
        }

        return $response;
    }

    /**
     * @param string[] $allowedOrigins
     */
    private function isOriginAllowed(ServerRequestInterface $request, array $allowedOrigins): bool
    {
        if ($allowedOrigins === ['*']) {
            return true;
        }

        if (!$request->hasHeader('Origin')) {
            return false;
        }

        $origin = $request->getHeaderLine('Origin');

        if (in_array($origin, $allowedOrigins)) {
            return true;
        }

        foreach ($allowedOrigins as $pattern) {
            if (preg_match($pattern, $origin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param non-empty-array<string> $allowedOrigins
     */
    private function configureAllowedOrigin(
        ResponseInterface $response,
        ServerRequestInterface $request,
        array $allowedOrigins,
        bool $supportsCredentials
    ): ResponseInterface {
        // Safe+cacheable, allow everything
        if ($allowedOrigins === ['*'] && !$supportsCredentials) {
            return $response->withHeader('Access-Control-Allow-Origin', '*');
        }

        // Single origins can be safely set
        if (count($allowedOrigins) === 1 && $allowedOrigins !== ['*'] && !$this->isRegex($allowedOrigins[0])) {
            return $response->withHeader('Access-Control-Allow-Origin', $allowedOrigins);
        }

        // For dynamic headers, set the requested Origin header when set and allowed
        if ($request->hasHeader('Origin') && $this->isOriginAllowed($request, $allowedOrigins)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $request->getHeader('Origin'));
        }

        return $this->varyHeader($response, 'Origin');
    }

    private function varyHeader(ResponseInterface $response, string $header): ResponseInterface
    {
        if (!$response->hasHeader('Vary')) {
            return $response->withHeader('Vary', $header);
        }

        if (!in_array($header, $response->getHeader('Vary'))) {
            return $response->withHeader('Vary', [...$response->getHeader('Vary'), $header]);
        }

        return $response;
    }

    private function isRegex(string $input): bool
    {
        return (bool) preg_match("/^\/.+\/[a-z]*$/i", $input);
    }
}
