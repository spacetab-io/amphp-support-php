Amphp support
=============

[![CircleCI](https://circleci.com/gh/spacetab-io/amphp-support-php/tree/master.svg?style=svg)](https://circleci.com/gh/spacetab-io/amphp-support-php/tree/master)
[![codecov](https://codecov.io/gh/spacetab-io/amphp-support-php/branch/master/graph/badge.svg)](https://codecov.io/gh/spacetab-io/amphp-support-php)

## Installation

```bash
composer require spacetab-io/amphp-support
```

## Usage

```php
use Amp\Http\{Server\HttpServer, Server\Request, Server\Response, Server\Router, Status};
use Amp\{Loop, Promise, Socket};
use HarmonyIO\Validation\Rule\{Combinator\All, Text\AlphaNumeric, Text\LengthRange};
use Spacetab\AmphpSupport\Handler\AbstractTrustedRequest;
use Spacetab\AmphpSupport\Server\ErrorHandler;
use Spacetab\AmphpSupport\Middleware\{AcceptJsonBody, ExceptionMiddleware};
use function Amp\call;

Loop::run(function () {
    $sockets = [
        Socket\Server::listen('0.0.0.0:8081'),
    ];

    $router = new Router();

    $handler = new class extends AbstractTrustedRequest {
        public function handleRequest(Request $request): Promise {
            var_dump($this->getTrustedBody());
            return call(fn() => new Response(Status::OK, [], '{"message": "hey!"}'));
        }
    };

    $router->stack(new ExceptionMiddleware());
    $router->addRoute('POST', '/', $handler, new class extends AcceptJsonBody {
        public function validate(): iterable {
            yield 'username' => new All(new LengthRange(3, 15), new AlphaNumeric());
        }
    });

    $server = new HttpServer($sockets, $router, new \Psr\Log\NullLogger());
    $server->setErrorHandler(new ErrorHandler());

    yield $server->start();

    // Stop the server gracefully when SIGINT is received.
    Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
        Loop::cancel($watcherId);
        yield $server->stop();
    });
});
```

```bash
curl -X POST -d '{"username": "roquie"}' -H 'Content-Type: application/json' http://0.0.0.0:8081/ | jq .
```

## Depends

* \>= PHP 7.4
* Composer for install package

## License

The MIT License

Copyright © 2020 spacetab.io, Inc. https://spacetab.io

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

