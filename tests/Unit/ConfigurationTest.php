<?php

namespace Elmage\TextNg\Test\Unit;

use Elmage\TextNg\Authentication\ApiKeyAuthentication;
use Elmage\TextNg\Configuration;
use Elmage\TextNg\HttpClient;
use Elmage\TextNg\Test\TestCase;

class ConfigurationTest extends TestCase
{
    public function testDefaultApiUrl()
    {
        $config = new Configuration(new ApiKeyAuthentication("TEST_KEY"), "Test");

        $this->assertEquals(Configuration::DEFAULT_API_URL, $config->getApiUrl());
    }

    public function testCustomApiUrl()
    {
        $config = new Configuration(new ApiKeyAuthentication("TEST_KEY"), "Test");
        $config->setApiUrl('https://example.com');
        $this->assertEquals('https://example.com', $config->getApiUrl());
    }

    public function testDefaultApiVersion()
    {
        $config = new Configuration(new ApiKeyAuthentication("TEST_KEY"), "Test");
        $this->assertEquals(Configuration::DEFAULT_API_VERSION, $config->getApiVersion());
    }

    public function testCustomApiVersion()
    {
        $config = new Configuration(new ApiKeyAuthentication("TEST_KEY"), "Test");
        $config->setApiVersion('2000-01-01');
        $this->assertEquals('2000-01-01', $config->getApiVersion());
    }

    public function testCreateHttpClient()
    {
        $config = new Configuration(new ApiKeyAuthentication("TEST_KEY"), "Test");
        $this->assertInstanceOf(HttpClient::class, $config->createHttpClient());
    }

    public function testApiKey()
    {
        $config = new Configuration(new ApiKeyAuthentication("TEST_KEY"), "Test");
        $this->assertInstanceOf(Configuration::class, Configuration::apiKey($config->getAuthentication()->getApiKey()));

        $key = "NEW_KEY";
        $config->getAuthentication()->setApiKey($key);
        $this->assertEquals($key, $config->getAuthentication()->getApiKey());

        $auth = new ApiKeyAuthentication("Random");
        $config->setAuthentication($auth);

        $this->assertEquals($auth, $config->getAuthentication());
    }

    public function testSenderAccessorAndModifier()
    {
        $config = new Configuration(new ApiKeyAuthentication("TEST_KEY"), "Test");
        $this->assertEquals("Test", $config->getSender());

        $config->setSender("Test2");
        $this->assertEquals("Test2", $config->getSender());
    }
}
