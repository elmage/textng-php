<?php

namespace Elmage\TextNg\Test\Unit;

use Elmage\TextNg\Enum\Param;
use Elmage\TextNg\Authentication\ApiKeyAuthentication;
use Elmage\TextNg\Test\TestCase;
use Elmage\TextNg\HttpClient;
use Elmage\TextNg\Configuration;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;

class HttpClientTest extends TestCase
{
    /** @var MockObject|ClientInterface */
    private $transport;

    /** @var HttpClient */
    private $client;

    public static function setUpBeforeClass(): void
    {
        date_default_timezone_set('Africa/Lagos');
    }

    protected function setUp(): void
    {
        $this->transport = $this->getMockBuilder(ClientInterface::class)->getMock();

        $authStub = $this->createStub(ApiKeyAuthentication::class);

        // Configure the stub.
        $authStub->method('getApiKey')
            ->will($this->returnValue("TEST_KEY"));

        $this->client = $this->createHttpClient($authStub);
    }

    protected function tearDown(): void
    {
        $this->transport = null;
        $this->client = null;
    }

    /**
     * @dataProvider provideForGetQueryString
     */
    public function testGetQueryString(string $path, string $expected): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->isRequestFor('GET', $expected))
            ->will($this->returnValue(new Response()));

        $this->client->get($path, ['foo' => 'bar']);
    }

    public function provideForGetQueryString(): array
    {
        return [
            ['/', '/?foo=bar&key=TEST_KEY'],
            ['/?bar=foo', '/?bar=foo&foo=bar&key=TEST_KEY'],
        ];
    }

    /**
     * @dataProvider provideForUnsafeMethod
     */
    public function testUnsafeMethod(string $method): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->isRequestFor(strtoupper($method), '/'))
            ->will($this->returnValue(new Response()));

        $this->client->$method('/', ['foo' => 'bar']);
    }

    public function provideForUnsafeMethod(): array
    {
        return [
            ['put'],
            ['post'],
            ['delete'],
        ];
    }

    public function testOptions(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with(
                $this->isRequestFor('POST', '/'),
                $this->isValidOptionsArray([])
            )
            ->will($this->returnValue(new Response()));

        $this->client->post('/', ['foo' => 'bar']);
    }

    /*------------------------------------------------------------------------------
    | PRIVATE METHODS
    /*------------------------------------------------------------------------------*/

    private function isRequestFor(string $method, string $path): Callback
    {
        return $this->callback(
            function ($request) use ($method, $path) {
                /** @var RequestInterface $request */
                $this->assertInstanceOf(RequestInterface::class, $request);
                $this->assertEquals($method, $request->getMethod());
                $this->assertEquals($path, $request->getRequestTarget());

                return true;
            }
        );
    }

    private function isValidOptionsArray(array $headers = [], $form_params = true): Callback
    {
        return $this->callback(function ($options) use ($headers, $form_params) {
            $this->assertArrayHasKey('headers', $options);
            $this->assertArrayHasKey('User-Agent', $options['headers']);
            $this->assertArrayHasKey('Content-Type', $options['headers']);

            if ($form_params) {
                $this->assertArrayHasKey(RequestOptions::FORM_PARAMS, $options);
            }

            foreach ($headers as $header) {
                $this->assertArrayHasKey($header, $options['headers']);
            }

            return true;
        });
    }

    private function createHttpClient(ApiKeyAuthentication $auth): HttpClient
    {
        return new HttpClient(
            Configuration::DEFAULT_API_URL,
            Configuration::DEFAULT_API_VERSION,
            $auth,
            "TestSender",
            $this->transport
        );
    }
}
