<?php

use Seba\HTTP\IncomingRequestHandler;
use Seba\HTTP\Exceptions\InvalidContentTypeException;

class IncomingRequestHandlerTest extends PHPUnit\Framework\TestCase
{
    public function testGetMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ["param1" => "value1", "param2" => "value2"];
        $requestHandler = new IncomingRequestHandler("application/x-www-form-urlencoded");

        $this->assertEquals('GET', $requestHandler->getMethod());
        $this->assertEquals($requestHandler->getBody(), $_GET);
    }

    public function testPostMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $requestHandler = new IncomingRequestHandler("application/x-www-form-urlencoded");
        $_POST = ["param1" => "value1", "param2" => "value2"];

        $method = $requestHandler->getMethod();

        $this->assertEquals('POST', $method);
        $this->assertEquals($requestHandler->getBody(), $_POST);
    }

    public function testThrowErrorWithInvalidContentType()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'invalid/content-type';

        $this->expectException(InvalidContentTypeException::class);
        $requestHandler = new IncomingRequestHandler();

        $requestHandler->getBody();
    }

}