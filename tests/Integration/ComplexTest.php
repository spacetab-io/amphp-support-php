<?php

declare(strict_types=1);

namespace Spacetab\Tests\AmphpSupport\Integration;

use Amp\Success;
use Psr\Log\NullLogger;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request as ClientRequest;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Router;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Socket;
use HarmonyIO\Validation\Rule\Combinator\All;
use HarmonyIO\Validation\Rule\Text\AlphaNumeric;
use HarmonyIO\Validation\Rule\Text\LengthRange;
use Spacetab\AmphpSupport\Handler\AbstractTrustedRequest;
use Spacetab\AmphpSupport\Handler\HealthcheckHandler;
use Spacetab\AmphpSupport\Server\ErrorHandler;
use Spacetab\AmphpSupport\Middleware\AcceptJsonBody;
use Spacetab\AmphpSupport\Middleware\ExceptionMiddleware;
use Spacetab\AmphpSupport\Server\Response;
use Spacetab\Transformation\DefaultTransformation;
use function Amp\call;
use Amp\PHPUnit\AsyncTestCase;

class ComplexTest extends AsyncTestCase
{
    private const LISTEN  = '0.0.0.0:8081';
    private const ADDRESS = 'http://' . self::LISTEN;

    private HttpClient $client;
    private HttpServer $server;

    public function setUp(): void
    {
        parent::setUp();

        $sockets = [
            Socket\Server::listen(self::LISTEN),
        ];

        $validationMiddleware = new class extends AcceptJsonBody {
            public function validate(): iterable {
                yield 'username' => new All(new LengthRange(3, 15), new AlphaNumeric());
            }
        };

        $router = new Router();
        $router->stack(new ExceptionMiddleware([
            'Text.AlphaNumeric' => 'Username must be alphanumeric.'
        ]));

        $router->addRoute('GET', '/', new HealthcheckHandler());
        $router->addRoute('GET', '/error', new CallableRequestHandler(function () {
            throw new \Exception('error');
        }));
        $router->addRoute('POST', '/post', new class extends AbstractTrustedRequest {
            public function handleRequest(Request $request): Promise {
                return new Success(Response::asJson(new DefaultTransformation($this->getTrustedBody())));
            }
        }, $validationMiddleware);
        $router->addRoute('POST', '/bad', new CallableRequestHandler(fn() => new Success), $validationMiddleware);
        $router->addRoute('POST', '/pdf-file-passed', new CallableRequestHandler(fn() => Response::asPdf('pdf', 'file.pdf')));
        $router->addRoute('POST', '/pdf-file-default', new CallableRequestHandler(fn() => Response::asPdf('pdf')));
        $router->addRoute('POST', '/html', new CallableRequestHandler(fn() => Response::asHtml('<p>hi</p>')));
        $this->server = new HttpServer($sockets, $router, new NullLogger());
        $this->server->setErrorHandler(new ErrorHandler());

        $this->client = HttpClientBuilder::buildDefault();
    }

    public function testHealthcheckHandler()
    {
        call(fn () => yield $this->server->start());

        /** @var \Amp\Http\Client\Response $response */
        $response = yield $this->client->request(new ClientRequest(self::ADDRESS));
        $contents = yield $response->getBody()->read();

        yield $this->server->stop();
        $this->assertSame('ok', $contents);
        $this->assertSame(Status::OK, $response->getStatus());
    }

    public function testServerErrorHandler()
    {
        call(fn () => yield $this->server->start());

        /** @var \Amp\Http\Client\Response $response */
        $response = yield $this->client->request(new ClientRequest(self::ADDRESS . '/error'));
        $contents = yield $response->getBody()->read();
        yield $this->server->stop();

        $expected = '{"error":{"message":"Internal Server Error","code":"AppError"}}';
        $this->assertJsonStringEqualsJsonString($expected, $contents);
        $this->assertSame(Status::INTERNAL_SERVER_ERROR, $response->getStatus());
    }

    public function testSuccessfulCaseForValidationMiddlewareAndTrustedHandler()
    {
        call(fn () => yield $this->server->start());

        $request = new ClientRequest(self::ADDRESS . '/post', 'POST', '{"username": "roquie"}');
        $request->addHeader('Content-Type', 'application/json');

        /** @var \Amp\Http\Client\Response $response */
        $response = yield $this->client->request($request);
        $contents = yield $response->getBody()->read();
        yield $this->server->stop();

        $this->assertJsonStringEqualsJsonString('{"data":{"username":"roquie"}}', $contents);
        $this->assertSame(Status::OK, $response->getStatus());
    }

    public function testUnsuccessfulCaseForValidationMiddlewareAndTrustedHandler()
    {
        call(fn () => yield $this->server->start());

        $request = new ClientRequest(self::ADDRESS . '/post', 'POST', '{"username": "__roquie"}');
        $request->addHeader('Content-Type', 'application/json');

        /** @var \Amp\Http\Client\Response $response */
        $response = yield $this->client->request($request);
        $contents = yield $response->getBody()->read();
        yield $this->server->stop();

        $expected = '{"error":{"message":"Error occurred in input data. Please, correct them and send request again.","code":"ValidationError","validation":{"username":["Username must be alphanumeric."]}}}';
        $this->assertJsonStringEqualsJsonString($expected, $contents);
        $this->assertSame(Status::UNPROCESSABLE_ENTITY, $response->getStatus());
    }

    public function testHowToServerAcceptNullableBody()
    {
        call(fn () => yield $this->server->start());

        $request = new ClientRequest(self::ADDRESS . '/post', 'POST');
        $request->addHeader('Content-Type', 'application/json; charset=utf-8');

        /** @var \Amp\Http\Client\Response $response */
        $response = yield $this->client->request($request);
        $contents = yield $response->getBody()->read();
        yield $this->server->stop();

        $expected = '{"error":{"message":"Body required","code":"AppError"}}';
        $this->assertJsonStringEqualsJsonString($expected, $contents);
        $this->assertSame(Status::BAD_REQUEST, $response->getStatus());
    }

    public function testHowToServerAcceptInvalidContentType()
    {
        call(fn () => yield $this->server->start());

        $request = new ClientRequest(self::ADDRESS . '/post', 'POST', '{}');
        $request->addHeader('Content-Type', 'application/xml');

        /** @var \Amp\Http\Client\Response $response */
        $response = yield $this->client->request($request);
        $contents = yield $response->getBody()->read();
        yield $this->server->stop();

        $expected = '{"error":{"message":"Content-Type not allowed. Supported `application/json`.","code":"AppError"}}';
        $this->assertJsonStringEqualsJsonString($expected, $contents);
        $this->assertSame(Status::UNSUPPORTED_MEDIA_TYPE, $response->getStatus());
    }

    public function testHowToServerAcceptInvalidJson()
    {
        call(fn () => yield $this->server->start());

        $request = new ClientRequest(self::ADDRESS . '/post', 'POST', '11\{}');
        $request->addHeader('Content-Type', 'application/json');

        /** @var \Amp\Http\Client\Response $response */
        $response = yield $this->client->request($request);
        $contents = yield $response->getBody()->read();
        yield $this->server->stop();

        $this->assertMatchesRegularExpression('/invalid json/', $contents);
        $this->assertSame(Status::BAD_REQUEST, $response->getStatus());
    }

    public function testHowToServerSendsUserGoAwayIfRequestHandlerNotTrusted()
    {
        call(fn () => yield $this->server->start());

        $request = new ClientRequest(self::ADDRESS . '/bad', 'POST', '{"username": "roquie"}');
        $request->addHeader('Content-Type', 'application/json');

        /** @var \Amp\Http\Client\Response $response */
        $response = yield $this->client->request($request);
        $contents = yield $response->getBody()->read();
        yield $this->server->stop();

        $expected = '{"error":{"message":"Your RequestHandler should be implemented as `Trusted RequestInterface` to receive parsed body.","code":"AppError"}}';
        $this->assertJsonStringEqualsJsonString($expected, $contents);
        $this->assertSame(Status::INTERNAL_SERVER_ERROR, $response->getStatus());
    }

    public function testWhereServerResponseAsPdfFileWithPassedFilename()
    {
        call(fn () => yield $this->server->start());

        $request = new ClientRequest(self::ADDRESS . '/pdf-file-passed', 'POST');

        /** @var \Amp\Http\Client\Response $response */
        $response = yield $this->client->request($request);
        $contents = yield $response->getBody()->read();
        $headers = $response->getHeaders();
        yield $this->server->stop();

        $this->assertSame(Status::OK, $response->getStatus());
        $this->assertSame('application/pdf', $headers['content-type'][0]);
        $this->assertSame('attachment; filename="file.pdf"', $headers['content-disposition'][0]);
        $this->assertSame('pdf', $contents);
    }

    public function testWhereServerResponseAsPdfFileWithDefaultFilename()
    {
        call(fn () => yield $this->server->start());

        $request = new ClientRequest(self::ADDRESS . '/pdf-file-default', 'POST');

        /** @var \Amp\Http\Client\Response $response */
        $response = yield $this->client->request($request);
        $contents = yield $response->getBody()->read();
        $headers = $response->getHeaders();
        yield $this->server->stop();

        $this->assertSame(Status::OK, $response->getStatus());
        $this->assertSame('application/pdf', $headers['content-type'][0]);
        $this->assertMatchesRegularExpression('/attachment\; filename\=\".*\"/', $headers['content-disposition'][0]);
        $this->assertSame('pdf', $contents);
    }

    public function testWhereServerResponseAsHtml()
    {
        call(fn () => yield $this->server->start());

        $request = new ClientRequest(self::ADDRESS . '/html', 'POST');

        /** @var \Amp\Http\Client\Response $response */
        $response = yield $this->client->request($request);
        $contents = yield $response->getBody()->read();
        $headers = $response->getHeaders();
        yield $this->server->stop();

        $this->assertSame(Status::OK, $response->getStatus());
        $this->assertSame('text/html', $headers['content-type'][0]);
        $this->assertSame('<p>hi</p>', $contents);
    }
}
