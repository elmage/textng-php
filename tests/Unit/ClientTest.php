<?php


namespace Elmage\TextNg\Test\Unit;

use Elmage\TextNg\Authentication\ApiKeyAuthentication;
use Elmage\TextNg\Client;
use Elmage\TextNg\Configuration;
use Elmage\TextNg\Enum\DeliveryReportReq;
use Elmage\TextNg\Enum\Param;
use Elmage\TextNg\Enum\Route;
use Elmage\TextNg\Exception\InvalidParamException;
use Elmage\TextNg\Exception\SendingLimitException;
use Elmage\TextNg\HttpClient;
use Elmage\TextNg\Test\TestCase;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ClientTest extends TestCase
{
    /** @var MockObject|HttpClient */
    private $http;

    /** @var Client */
    private $client;

    public static function setUpBeforeClass(): void
    {
        date_default_timezone_set('Africa/Lagos');
    }

    protected function setUp(): void
    {
        $this->http = $this->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->http->expects($this->any())
            ->method('getSender')
            ->willReturn('Test');

        $this->client = new Client($this->http);
    }

    protected function tearDown(): void
    {
        $this->http = null;
        $this->client = null;
    }

    public function testGetBalance()
    {
        $resText = '{"D":{"details":[' .
            '{"key":"TEST_KEY","unitsbalance":"20","time":"2000-01-01 01:10:15","status":"successful"}' .
            ']}}';

        $this->http->expects($this->any())
            ->method('get')
            ->with('/smsbalance/', [Param::CHECK_BALANCE => 1])
            ->willReturn($this->getMockResponse(
                $resText
            ));

        $actual = $this->client->getBalance();
        $this->assertIsArray($actual, "Array expected, got " . gettype($actual));

        $keys = ["unitsbalance", "time", "status"];
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $actual, "Expected array to contain {$key}");
        }

        $this->assertArrayNotHasKey(Param::API_KEY, $actual, "Array should not contain API key.");
    }

    /**
     * @dataProvider provideForTestSendSMS
     */
    public function testSendSMS(int $route, string $phone, string $message, string $sender, string $bypasscode)
    {
        $this->http->expects($this->any())
            ->method('post')
            ->with('/pushsms/', [
                Param::ROUTE => $route,
                Param::PHONE => $phone,
                Param::MESSAGE => $message,
                Param::SENDER => $sender,
                Param::BYPASSCODE => $bypasscode
            ])
            ->willReturn($this->getMockResponse(
                '5 units used|| Status:Successful|| Route:5|| Type:single number|| Reference:4567ygfrthyi'
            ));

        $actual = $this->client->sendOTP($route, $phone, $message, $bypasscode);

        $this->assertIsArray($actual, "Array expected, got " . gettype($actual));

        $keys = ["units_used", "status", "type", "reference", "route"];

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $actual, "Expected array to contain {$key}");
        }
    }

    public function provideForTestSendSMS(): array
    {
        return [
            //route phone     message          sender  bypasscode
            [3, '0806754345', "Otp is 675543", "Test", "2345678909"],
            [4, '0806754345', "Otp is 675543", "Test", "2345678909"],
            [5, '0806754345', "Otp is 675543", "Test", "2345678909"],
            [6, '0806754345', "Otp is 675543", "Test", "2345678909"],
        ];
    }


    /**
     * @dataProvider provideForTestCreateCustomer
     */
    public function testCreateCustomer(
        string $customerName,
        string $customerPhone,
        string $categoryID,
        string $response,
        array $keys = []
    )
    {
        $this->http->expects($this->any())
            ->method('get')
            ->with('/addcustomer/', [
                Param::CUSTOMER_NAME => $customerName,
                Param::CUSTOMER_PHONE => $customerPhone,
                Param::CATEGORY_ID => $categoryID,
            ])
            ->willReturn($this->getMockResponse($response));

        $actual = $this->client->createCustomer($customerName, $customerPhone, $categoryID);
        $this->assertIsArray($actual, "Array expected, got " . gettype($actual));

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $actual, "Expected array to contain {$key}");
        }
    }

    public function provideForTestCreateCustomer(): array
    {
        return [
            //route phone     message          sender  bypasscode
            [
                "Chidi",
                '08060000000',
                "675543987",
                '{"D":{"details":[
                {"customerphone":"08060000000","customername":"Chidi","catid":"675543987","status":"successful"}
                ]}}',
                ['customerphone', 'customername', 'catid', 'status']
            ],
            [
                "Samuel",
                '08060000001',
                "675543987",
                '{"D":{"details":[
                {"customerphone":"08060000001","customername":"Samuel","catid":"675543876",
                "status":"error-customerphone-exists-in-category"}
                ]}}',
                ['customerphone', 'customername', 'catid', 'status']
            ],
            [
                "Grace",
                '08060000002',
                "675543987",
                '{"D":{"details":[{"status":"customerphone-not-exists"}]}}',
                ['status']
            ]
        ];
    }


    /**
     * @dataProvider provideForTestRemoveCustomer
     */
    public function testRemoveCustomer(
        string $customerPhone,
        string $categoryID,
        string $response,
        array $keys = []
    )
    {
        $this->http->expects($this->any())
            ->method('get')
            ->with('/removecustomer/', [
                Param::CUSTOMER_PHONE => $customerPhone,
                Param::CATEGORY_ID => $categoryID,
            ])
            ->willReturn($this->getMockResponse($response));

        $actual = $this->client->removeCustomer($customerPhone, $categoryID);
        $this->assertIsArray($actual, "Array expected, got " . gettype($actual));

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $actual, "Expected array to contain {$key}");
        }
    }

    public function provideForTestRemoveCustomer(): array
    {
        return [
            //route phone     message          sender  bypasscode
            [
                '08060000000',
                "675543987",
                '{"D":{"details":[
                {"customerphone":"08060000000","customername":"Chidi","catid":"675543987","status":"successful"}
                ]}}',
                ['customerphone', 'customername', 'catid', 'status']
            ],
            [
                '08060000001',
                "675543987",
                '{"D":{"details":[
                {"customerphone":"08060000001","customername":"Samuel","catid":"675543876",
                "status":"error-customerphone-does-not-exists-in-category"}
                ]}}',
                ['customerphone', 'customername', 'catid', 'status']
            ],
            [
                '08060000002',
                "675543987",
                '{"D":{"details":[{"status":"customerphone-not-exists"}]}}',
                ['status']
            ]
        ];
    }


    public function testSendSMSErrorCase()
    {
        $this->http->expects($this->any())
            ->method('post')
            ->with('/pushsms/', [
                Param::ROUTE => Route::RECOMMENDED,
                Param::PHONE => "0806",
                Param::MESSAGE => "TEST",
                Param::SENDER => "Test",
                Param::BYPASSCODE => "test"
            ])
            ->willReturn($this->getMockResponse('ERROR insufficient units'));

        $actual = $this->client->sendSMS(Route::RECOMMENDED, ["0806"], "TEST", "test");

        $this->assertIsArray($actual, "Array expected, got " . gettype($actual));

        $keys = ["status", "message"];
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $actual, "Expected array to contain {$key}");
        }
    }

    public function testSendSMSThrowsInvalidParamExceptions()
    {
        $this->expectException(InvalidParamException::class);
        $this->client->sendSMS(7, ["0806709"], "Sample Message", "088776");
    }


    public function testSendSMSThrowsSendingLimitExceptions()
    {
        $this->expectException(SendingLimitException::class);
        $this->client->sendSMS(Route::RECOMMENDED, range(100, 100500), "Sample Message", "088776");
    }

    public function testGetDeliveryReport()
    {
        $ref = "9837092678";

        $this->http->expects($this->any())
            ->method('get')
            ->with('/deliveryreport/', [
                Param::USED_ROUTE => Route::RECOMMENDED,
                Param::REFERENCE => $ref,
                Param::REQ => DeliveryReportReq::ALL
            ])
            ->willReturn($this->getMockResponse(
                '{"D":{"details":[{"number":"234XXXXXXXXX","status":"DELIVERED","track_id":"xxxxxxxxxx"},
                {"number":"234XXXXXXXXX","status":"DELIVRD","track_id":"xxxxxxxxxx"},
                {"number":"234XXXXXXXXX","status":"SENT-VIA-BYPASS","track_id":"xxxxxxxxxx"},
                {"number":"234XXXXXXXXX","status":"SENT-BUT-PENDING","track_id":"xxxxxxxxxx"},
                {"number":"234XXXXXXXXX","status":"BLOCKED","track_id":"xxxxxxxxxx"}]}}'
            ));

        $actual = $this->client->getDeliveryReport($ref, DeliveryReportReq::ALL, Route::RECOMMENDED);

        $this->assertIsArray($actual, "Array expected, got " . gettype($actual));
        $this->assertArrayHasKey("data", $actual, "Expected array to contain 'details'");

        $this->assertIsArray($actual['data'], "Array expected, got " . gettype($actual['data']));

        $test_data = $actual['data'][0];
        $keys = ["number", "status", "track_id"];

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $test_data, "Expected array item to contain {$key}");
        }

        $this->expectException(InvalidParamException::class);
        $this->client->getDeliveryReport($ref, DeliveryReportReq::ALL, 10);
    }


    public function testCreateMethod()
    {
        $config = new Configuration(new ApiKeyAuthentication("TEST"), "Test");
        $client = Client::create($config);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertInstanceOf(HttpClient::class, $client->getHttpClient());

        $client->setSender("Test2");

        $this->assertEquals("Test2", $client->getHttpClient()->getSender());
    }
    /*------------------------------------------------------------------------------
    | PRIVATE METHODS
    /*------------------------------------------------------------------------------*/


    /**
     * @param $data
     * @return MockObject|ResponseInterface
     */
    private function getMockResponse($data)
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        if (is_array($data)) {
            $data = json_encode((object)$data);
        }

        $response->expects($this->any())
            ->method('getBody')
            ->willReturn($stream);
        $stream->expects($this->any())
            ->method('__toString')
            ->willReturn($data);

        return $response;
    }
}
