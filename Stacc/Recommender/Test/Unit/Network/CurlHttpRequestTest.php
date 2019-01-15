<?php

namespace Stacc\Recommender\Network;

use Magento\Framework\HTTP\Client\Curl;
use PHPUnit\Framework\TestCase;
use Stacc\Recommender\Logger\Logger;

class CurlHttpRequestTest extends TestCase
{
    const TEST_URL = 'https://test.url/api/v2';
    const TEST_SHOP_ID = "test_shop_id";
    const TEST_API_KEY = "test_api_key";
    const TEST_TIMEOUT = 3000;

    private $mockLogger;
    private $mockCurl;
    private $mockEnvironment;
    private $curlHttpRequestInstance;

    protected function setUp()
    {
        $this->mockLogger = $this->createMock(Logger::class);
        $this->mockCurl = $this->createMock(Curl::class);
        $this->mockEnvironment = $this->createMock(Environment::class);
        $this->curlHttpRequestInstance = new CurlHttpRequest($this->mockEnvironment,$this->mockCurl, $this->mockLogger);
    }

    public function testPostDataReturnsValueIfResponeCode200()
    {
        $this->assertMethodsForPostDataAreRun(200);
        $this->mockLogger->expects($this->never())->method("error")->willReturn("{}");
        $result = $this->curlHttpRequestInstance->postData([], $this::TEST_URL, $this::TEST_TIMEOUT);
        $this->assertEquals("{}", $result);
    }

    public function testPostDataReturnsValueIfResponeCode500()
    {
        $this->assertMethodsForPostDataAreRun(500);
        $this->mockLogger->expects($this->once())->method("error")->willReturn("{}");
        $result = $this->curlHttpRequestInstance->postData([], $this::TEST_URL, $this::TEST_TIMEOUT);
        $this->assertEquals("{}", $result);
    }

    public function testPostDataReturnsValueIfExceptionIsThrown()
    {
        $this->mockCurl->expects($this->once())->method("setOptions")->willReturn("This function ended in exception")->willThrowException(new \Exception);
        $this->mockLogger->expects($this->never())->method("error")->willReturn("{}");
        $this->mockLogger->expects($this->once())->method("critical")->willReturn("{}");
        $result = $this->curlHttpRequestInstance->postData([], $this::TEST_URL, $this::TEST_TIMEOUT);
        $this->assertEquals('{"error": "Failed to start a connection!"}', $result);
    }

    /**
     * @param int $statusCode
     */
    private function assertMethodsForPostDataAreRun($statusCode)
    {
        $this->mockEnvironment->expects($this->once())->method("getCredentials")->willReturn(["id" => $this::TEST_SHOP_ID, "key" => $this::TEST_API_KEY]);
        $this->mockCurl->expects($this->once())->method("setOptions")->willReturn($this->mockCurl);
        $this->mockCurl->expects($this->once())->method("post")->willReturn($this->mockCurl);
        $this->mockCurl->expects($this->once())->method("getBody")->willReturn("{}");
        $this->mockCurl->expects($this->once())->method("getStatus")->willReturn($statusCode);
    }
}