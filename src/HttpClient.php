<?php

namespace Elmage\TextNg;

use Elmage\TextNg\Authentication\ApiKeyAuthentication;
use Elmage\TextNg\Enum\Param;
use Elmage\TextNg\Exception\HttpException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class HttpClient
{
    private $apiUrl;
    private $apiVersion;
    private $auth;
    private $transport;

    /** @var string Text Message Sender */
    private $sender;

    /** @var LoggerInterface */
    private $logger;

    /** @var RequestInterface */
    private $lastRequest;

    /** @var ResponseInterface */
    private $lastResponse;

    public function __construct(
        string $apiUrl,
        string $apiVersion,
        ApiKeyAuthentication $auth,
        string $sender,
        ClientInterface $transport
    ) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiVersion = $apiVersion;
        $this->auth = $auth;
        $this->sender = $sender;
        $this->transport = $transport;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function setSender(string $sender)
    {
        $this->sender = $sender;
    }

    /** @return ResponseInterface */
    public function get($path, array $params = array()): ResponseInterface
    {
        return $this->request('GET', $path, $params);
    }

    /** @return ResponseInterface */
    public function post($path, array $params = array()): ResponseInterface
    {
        return $this->request('POST', $path, $params);
    }

    /** @return ResponseInterface */
    public function put($path, array $params = array()): ResponseInterface
    {
        return $this->request('PUT', $path, $params);
    }

    /** @return ResponseInterface */
    public function delete($path, array $params = array()): ResponseInterface
    {
        return $this->request('DELETE', $path, $params);
    }

    /**
     * @param string $method http request method
     * @param string $path   url path
     * @param array  $params request parameters
     */
    private function request(string $method, string $path, array $params = array()): ResponseInterface
    {
        $method = strtoupper($method);

        $params[Param::API_KEY] = $this->auth->getApiKey();

        if ($method === 'GET') {
            $path = $this->prepareQueryString($path, $params);
        }

        $request = new Request($method, $this->prepareUrl($path));

        return $this->send($request, $params);
    }

    private function send(RequestInterface $request, array $params = array()): ResponseInterface
    {
        $this->lastRequest = $request;

        $options = $this->prepareOptions(
            $params
        );

        try {
            $this->lastResponse = $response = $this->transport->send($request, $options);
        } catch (RequestException $e) {
            throw HttpException::wrap($e);
        }

        if ($this->logger) {
            //$this->logWarnings($response); TODO LOG warnings
        }

        return $response;
    }

    /**
     * @param $path //request path
     * @param array $params pointer to an array of parameters
     *
     * @return string
     */
    private function prepareQueryString(string $path, array &$params = array()): string
    {
        if (count($params) < 1) {
            return $path;
        }

        $path .= false === strpos($path, '?') ? '?' : '&';
        $path .= http_build_query($params, '', '&');

        return $path;
    }

    private function prepareOptions(array $params = array())
    {
        $options = array();

        if (count($params) > 0) {
            $options[RequestOptions::JSON] = $params;
            $body = json_encode($params);
        } else {
            $body = '';
        }

        $defaultHeaders = array(
            'User-Agent' => 'elmage/textng/php/'.Client::VERSION,
            'Content-Type' => 'application/json',
            'Accept' => 'text/plain, application/json',
        );

        $options[RequestOptions::HEADERS] = $defaultHeaders;

        return $options;
    }

    private function prepareUrl(string $path): string
    {
        return $this->apiUrl.'/'.ltrim($path, '/');
    }
}
