<?php

namespace Elmage\TextNg;

use Elmage\TextNg\Authentication\ApiKeyAuthentication;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;

class Configuration
{
    const DEFAULT_API_URL = 'https://api.textng.xyz';
    const DEFAULT_API_VERSION = '1';

    private ApiKeyAuthentication $authentication;
    private $apiUrl;
    private $sender;
    private $apiVersion;
    private $logger;

    public function __construct(ApiKeyAuthentication $authentication, string $sender)
    {
        $this->authentication = $authentication;
        $this->apiUrl = self::DEFAULT_API_URL;
        $this->apiVersion = self::DEFAULT_API_VERSION;
        $this->sender = $sender;
    }

    /** @return HttpClient */
    public function createHttpClient(ClientInterface $transport = null)
    {
        $httpClient = new HttpClient(
            $this->apiUrl,
            $this->apiVersion,
            $this->authentication,
            $this->sender,
            $transport ?: new GuzzleClient()
        );

        $httpClient->setLogger($this->logger);

        return $httpClient;
    }

    /**
     * Creates a new configuration with API key authentication.
     *
     * @param string $apiKey    An API key
     * @param string $sender    sender name
     * @param string $apiSecret An API secret
     *
     * @return Configuration A new configuration instance
     */
    public static function apiKey(string $apiKey, string $sender = '')
    {
        return new static(
            new ApiKeyAuthentication($apiKey),
            $sender
        );
    }

    public function getAuthentication()
    {
        return $this->authentication;
    }

    public function setAuthentication(ApiKeyAuthentication $authentication)
    {
        $this->authentication = $authentication;
    }

    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }

    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    public function setApiVersion($apiVersion)
    {
        $this->apiVersion = $apiVersion;
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function setSender(string $sender)
    {
        $this->sender = $sender;
    }
}
