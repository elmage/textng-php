<?php

namespace Elmage\TextNg\Test\Unit\Exception;

use Elmage\TextNg\Client;
use Elmage\TextNg\Exception\BadRequestException;
use Elmage\TextNg\Exception\HttpException;
use Elmage\TextNg\Exception\InternalServerException;
use Elmage\TextNg\Exception\NotFoundException;
use Elmage\TextNg\Exception\ServiceUnavailableException;
use Elmage\TextNg\Exception\UnauthorizedException;
use Elmage\TextNg\Exception\ValidationException;
use Elmage\TextNg\HttpClient;
use Elmage\TextNg\Test\TestCase;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

class HttpExceptionTest extends TestCase
{

    /**
     * @dataProvider providerForTestHttpException
     */
    public function testHttpException($statusCode)
    {
        $request = new Request('GET', "http://example.com");
        $response = new Response($statusCode);

        $exception = RequestException::create($request, $response);

        $e = HttpException::wrap($exception);

        switch ($statusCode) {
            case 400:
                $this->assertInstanceOf(BadRequestException::class, $e);
                break;
            case 401:
                $this->assertInstanceOf(UnauthorizedException::class, $e);
                break;
            case 404:
                $this->assertInstanceOf(NotFoundException::class, $e);
                break;
            case 422:
                $this->assertInstanceOf(ValidationException::class, $e);
                break;
            case 500:
                $this->assertInstanceOf(InternalServerException::class, $e);
                break;
            case 503:
                $this->assertInstanceOf(ServiceUnavailableException::class, $e);
                break;
            default:
                $this->assertInstanceOf(HttpException::class, $e);
                break;
        }

        $this->assertEquals($request, $e->getRequest());
        $this->assertEquals($response, $e->getResponse());
        $this->assertEquals($statusCode, $e->getStatusCode());
    }

    public function providerForTestHttpException()
    {
        return [
            [400],
            [401],
            [404],
            [422],
            [500],
            [503],
            [505]
        ];
    }
}
