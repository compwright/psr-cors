# compwright/psr-cors

Library and middleware enabling cross-origin resource sharing (CORS) for your
PHP interoperable application, utilizing the PSR-7 and PSR-15 standards.

It attempts to implement the [W3C Recommendation](http://www.w3.org/TR/cors/) for cross-origin resource sharing.

Build status: ![.github/workflows/run-tests.yml](https://github.com/compwright/psr-cors/workflows/.github/workflows/run-tests.yml/badge.svg)

## Installation

Require `compwright/psr-cors` using composer.

## Usage

This package can be used as a library or as PSR-15 middleware.

### Options

| Option                | Description                                                | Default value |
|-----------------------|------------------------------------------------------------|---------------|
| `allowedMethods`      | Matches the request method.                                | All           |
| `allowedOrigins`      | Matches the request origin (supports regex).               | All           |
| `allowedHeaders`      | Sets the Access-Control-Allow-Headers response header.     | All           |
| `exposedHeaders`      | Sets the Access-Control-Expose-Headers response header.    | None          |
| `maxAge`              | Sets the Access-Control-Max-Age response header. Set to `null` to omit the header/use browser default. | None |
| `supportsCredentials` | Sets the Access-Control-Allow-Credentials header.          | None          |

The _allowedMethods_ and _allowedHeaders_ options are case-insensitive.

If `true` is provided to _allowedMethods_, _allowedOrigins_ or _allowedHeaders_ all methods/origins/headers are allowed.

If _supportsCredentials_ is `true`, you must [explicitly set](https://fetch.spec.whatwg.org/#cors-protocol-and-credentials) `allowedHeaders` for any headers which are not CORS safelisted.

## Example: using middleware

```php
<?php

use Compwright\PsrCors\Middleware;

$middleware = Middleware::create(
    responseFactory: $psrResponseFactory,
    allowedHeaders: ['x-allowed-header', 'x-other-allowed-header'],    
    allowedMethods: ['DELETE', 'GET', 'POST', 'PUT'],
    allowedOrigins: ['localhost'],
    exposedHeaders: [],
    maxAge: 600,
    supportsCredentials: false
);

$response = $middleware->handle($request, $app);
```
