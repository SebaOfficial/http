<?php

use Seba\HTTP\ResponseHandler;

class ResponseHandlerTest extends PHPUnit\Framework\TestCase
{
    public function testSendResponse(): void
    {
        $responseHandler = new ResponseHandler();
        $responseHandler->setHttpCode(200);
        $responseHandler->setBody(['message' => 'Success']);

        ob_start();
        $responseHandler->send(false);
        $output = ob_get_clean();

        $this->assertEquals($output, json_encode(['message' => 'Success']));
        $this->assertSame(200, http_response_code());
    }
}