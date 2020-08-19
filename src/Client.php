<?php

namespace Elmage\TextNg;

use Elmage\TextNg\Enum\Param;
use Elmage\TextNg\Enum\Route;
use Elmage\TextNg\Exception\InvalidParamException;
use Elmage\TextNg\Exception\SendingLimitException;

class Client
{
    const VERSION = '0.1.0';

    private HttpClient $http;

    /** @var int The max number of recipients in one transaction */
    private int $bulkLimit = 100000;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Creates a new TextNg client.
     *
     * @param Configuration $configuration
     *
     * @return Client A new TextNg client
     */
    public static function create(Configuration $configuration)
    {
        return new static(
            $configuration->createHttpClient()
        );
    }

    public function getHttpClient()
    {
        return $this->http;
    }

    /**
     * @return array
     */
    public function getBalance(): array
    {
        $params = array(
            Param::CHECK_BALANCE => 1,
        );

        return $this->makeRequest('/smsbalance/', 'get', $params);
    }

    /**
     * @param int    $route
     * @param string $phoneNumber
     * @param string $message
     * @param string $bypassCode
     * @param array  $params
     *
     * @return array
     */
    public function sendOTP(
        int $route,
        string $phoneNumber,
        string $message,
        string $bypassCode = '',
        array $params = array()
    ): array {
        return $this->sendSMS($route, array($phoneNumber), $message, $bypassCode, $params);
    }

    /**
     * @param int    $route
     * @param array  $phoneNumbers
     * @param string $message
     * @param string $bypassCode
     * @param array  $params
     *
     * @return array
     */
    public function sendSMS(
        int $route,
        array $phoneNumbers,
        string $message,
        string $bypassCode = '',
        array $params = array()
    ): array {
        if (!in_array($route, Route::$routes)) {
            throw new InvalidParamException("Invalid 'Route' parameter supplied");
        }

        if (count($phoneNumbers) > $this->bulkLimit) {
            throw new SendingLimitException('Too many recipients');
        }

        $phoneNumbers = implode(',', $phoneNumbers);

        $params[Param::SENDER] = $this->http->getSender();
        $params[Param::ROUTE] = $route;
        $params[Param::PHONE] = trim($phoneNumbers, ',');
        $params[Param::MESSAGE] = $message;

        if ($bypassCode) {
            $params[Param::BYPASSCODE] = $bypassCode;
        }

        return $this->makeRequest('/pushsms/', 'post', $params);
    }

    public function getDeliveryReport(string $reference, $req, $used_route)
    {
        if (!in_array($used_route, Route::$routes)) {
            throw new InvalidParamException("Invalid 'used_route' parameter supplied");
        }

        $params = array(
            Param::REFERENCE => $reference,
            Param::REQ => $req,
            Param::USED_ROUTE => $used_route,
        );

        return $this->makeRequest('/deliveryreport/', 'get', $params);
    }

    public function setSender(string $sender): void
    {
        $this->http->setSender($sender);
    }

    /**------------------------------------------------------------------------------
     * | PRIVATE METHODS
     * /*------------------------------------------------------------------------------*/

    /**
     * This method makes the intended request than parses the response body
     * and returns an array of the the response data.
     *
     * @param string $path
     * @param string $method
     * @param array  $params
     *
     * @return array
     */
    private function makeRequest(string $path, string $method, array $params): array
    {
        $response = $this->http->$method($path, $params);
        $body = $response->getBody();

        // check if response by is encapsulated in curly braces
        // this indicates if the response is JSON or not
        // Yeah, I'm not checking the response header for content type because
        // The API docs are not consistent with certain things

        if (substr($body, 0, 1) == '{' && substr($body, -1) == '}') {
            return $this->requestAndExtractDetails($body);
        } else {
            return $this->requestAndParseTextResponse($body);
        }
    }

    /**
     * This method is only used where the response body is Plain Text and extra parsing and
     * formatting has to be done.
     *
     * @param string $body
     *
     * @return array
     */
    private function requestAndParseTextResponse(string $body): array
    {
        //if the response body starts with the string "ERROR"
        //return an array showing the status as "error" and set the error message
        //to the remainder part of the response body
        if (substr($body, 0, 5) == 'ERROR') {
            return array(
                'status' => 'error',
                'message' => substr($body, 6),
            );
        }

        $body = explode('||', $body);

        // Format each section of the body delimited by "||" into key value pairs
        // with the keys being in all lower case
        $data = array();

        $i = 0;
        foreach ($body as $item) {
            if (0 == $i && strpos($item, 'units')) {
                $data['units_used'] = trim(explode(' ', $item)[0]);
            } else {
                $data_parts = explode(':', $item);
                @$data[strtolower(trim($data_parts[0]))] = trim($data_parts[1]);
            }

            $i++;
        }

        // Remove the API key from the response
        unset($data[Param::API_KEY]);

        return $data;
    }

    /**
     * This method is only used where the response body is JSON.
     *
     * @param string $body
     *
     * @return array
     */
    private function requestAndExtractDetails(string $body): array
    {
        $details = $this->decode($body)['D']['details'];

        if (count($details) == 1) {
            $details = $details[0];
        } else {
            $details = array('data' => $details);
        }

        if (array_key_exists(Param::API_KEY, $details)) {
            unset($details[Param::API_KEY]);
        }

        return $details;
    }

    /**
     * @param $data
     * @param bool $assoc
     *
     * @return array
     */
    private function decode($data, $assoc = true): array
    {
        return json_decode($data, $assoc);
    }
}
