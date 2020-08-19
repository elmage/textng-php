<?php

namespace Elmage\TextNg\Exception;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpException extends RuntimeException
{
    private $request;
    private $response;

    /**
     * Wraps an API exception in the appropriate domain exception.
     *
     * @param RequestException $e The API exception
     *
     * @return HttpException
     */
    public static function wrap(RequestException $e)
    {
        $response = $e->getResponse();

        $class = self::exceptionClass($response);
        $message = $e->getMessage();

        return new $class($message, $e->getRequest(), $response, $e);
    }

    public function __construct($message, RequestInterface $request, ResponseInterface $response, \Exception $previous)
    {
        parent::__construct($message, 0, $previous);

        $this->request = $request;
        $this->response = $response;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    private static function exceptionClass(ResponseInterface $response)
    {
        switch ($response->getStatusCode()) {
            case 400:
                return BadRequestException::class;
            case 401:
                return UnauthorizedException::class;
            case 404:
                return NotFoundException::class;
            case 422:
                return ValidationException::class;
            case 500:
                return InternalServerException::class;
            case 503:
                return ServiceUnavailableException::class;
            default:
                return HttpException::class;
        }
    }
}
