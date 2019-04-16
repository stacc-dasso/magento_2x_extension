<?php

namespace Stacc\Recommender\Network;

use PHPUnit\Framework\TestCase;
use Magento\Cookie\Helper\Cookie;
use Stacc\Recommender\Logger\Logger;

class ApiclientTest extends TestCase
{
    const TEST_URL = 'https://test.url/api/v2';
    const TEST_WEBSITE = 'Test Default';
    const TEST_TIMEOUT = 3000;
    const TEST_STORE_CODE = "test_store_code";
    const TEST_USER_AGENT = "test_user_agent";

    const TEST_SESSION_ID = 'stacc_test_session';
    const TEST_VISITOR_ID = 'stacc_test_visitor';
    const TEST_CUSTOMER_ID = 'stacc_test_customer';
    const TEST_LANG = 'EN_US';
    const TEST_CURRENCY = 'USD';
    const TEST_VERSION = '2.0.0';
    const TEST_PRODUCT_ID = "134";

    const CURRENT_TIME = 100000;

    private $mockLogger;

    private $mockCookie;

    private $mockEnvironment;

    private $mockHttpRequest;

    private $apiclientInstance;

    protected function setUp()
    {
        $this->mockLogger = $this->createMock(Logger::class);
        $this->mockCookie = $this->createMock(Cookie::class);
        $this->mockEnvironment = $this->createMock(Environment::class);
        $this->mockHttpRequest = $this->createMock(HttpRequestInterface::class);
        $this->apiclientInstance = new Apiclient(
            $this->mockEnvironment,
            $this->mockHttpRequest,
            $this->mockCookie,
            $this->mockLogger
        );
    }

    // Get Instances In Apiclient

    public function testReturnsEnvironment()
    {
        $this->assertInstanceOf(Environment::class, $this->apiclientInstance->getEnvironment());
    }

    public function testReturnsLogger()
    {
        $this->assertInstanceOf(Logger::class, $this->apiclientInstance->getLogger());
    }

    public function testReturnsHttpRequest()
    {
        $this->assertInstanceOf(HttpRequestInterface::class, $this->apiclientInstance->getHttpRequest());
    }

    // Recommendation Event Related Method tests
    public function testAskRecommendationReturnsResponse()
    {
        $this->assertPropertiesMethodsAreCalledOnce();
        $this->assertDataForRequestIsCalled("getRecommendationsURL");
        $result = $this->apiclientInstance->askRecommendations($this::TEST_PRODUCT_ID, "stacc_product_default");
        $this->assertInternalType("array", $result);
    }

    public function testAskRecommendationsExceptionReturnsResponse()
    {
        $this->mockHttpRequestThrowsException("postData", "{}", new \Exception);
        $result = $this->apiclientInstance->askRecommendations($this::TEST_PRODUCT_ID, "stacc_product_default");
        $this->assertInternalType("array", $result);
    }

    // View Event Related Method tests
    public function testSendViewEventReturnsResponse()
    {
        $this->assertPropertiesMethodsAreCalledOnce();
        $this->assertDataForRequestIsCalled("getViewEventURL");
        $this->mockHttpRequestReturnsApprovedResponse("postData", "{}");
        $this->assertEquals("{}", $this->apiclientInstance->sendViewEvent($this::TEST_PRODUCT_ID));
    }

    public function testSendViewEventExceptionReturnsResponse()
    {
        $this->mockHttpRequestThrowsException("postData", "{}", new \Exception);
        $result = $this->apiclientInstance->sendViewEvent($this::TEST_PRODUCT_ID);
        $this->assertEquals("{}", $result);
    }

    // Search Event Related Method tests
    public function testSearchEventReturnsResponse()
    {
        $this->assertPropertiesMethodsAreCalledOnce();
        $this->assertDataForRequestIsCalled("getSearchEventURL");
        $this->mockHttpRequestReturnsApprovedResponse("postData", "{}");
        $result = $this->apiclientInstance->sendSearchEvent("testSearch");
        $this->assertEquals("{}", $result);
    }

    public function testSearchEventExceptionReturnsResponse()
    {
        $this->mockHttpRequestThrowsException("postData", "{}", new \Exception);
        $result = $this->apiclientInstance->sendSearchEvent("testSearch");
        $this->assertEquals("{}", $result);
    }

    // AddToCart Event Related Method tests
    public function testAddToCartEventReturnsResponse()
    {
        $this->assertPropertiesMethodsAreCalledOnce();
        $this->assertDataForRequestIsCalled("getAddToCartEventURL");
        $this->mockHttpRequestReturnsApprovedResponse("postData", "{}");
        $result = $this->apiclientInstance->sendAddToCartEvent($this::TEST_PRODUCT_ID);
        $this->assertEquals("{}", $result);
    }

    public function testAddToCartEventExceptionReturnsResponse()
    {
        $this->mockHttpRequestThrowsException("postData", "{}", new \Exception);
        $result = $this->apiclientInstance->sendAddToCartEvent($this::TEST_PRODUCT_ID);
        $this->assertEquals("{}", $result);
    }

    // Purchase Event Related Method tests
    public function testPurchaseEventReturnsResponse()
    {
        $this->assertPropertiesMethodsAreCalledOnce();
        $this->assertDataForRequestIsCalled("getPurchaseEventURL");
        $this->mockHttpRequestReturnsApprovedResponse("postData", "{}");
        $result = $this->apiclientInstance->sendPurchaseEvent($this::TEST_PRODUCT_ID);
        $this->assertEquals("{}", $result);
    }

    public function testPurchaseEventExceptionReturnsResponse()
    {
        $this->mockHttpRequestThrowsException("postData", "{}", new \Exception);
        $result = $this->apiclientInstance->sendPurchaseEvent($this::TEST_PRODUCT_ID);
        $this->assertEquals("{}", $result);
    }

    public function testSendingProductsReturnsResponse()
    {
        $this->assertDataForRequestIsCalled("getCatalogSyncURL");
        $this->mockHttpRequestReturnsApprovedResponse("postData", "{}");
        $result = $this->apiclientInstance->sendProducts([]);
        $this->assertEquals("{}", $result);
    }

    public function testSendingProductsExceptionReturnsResponse()
    {
        $this->mockHttpRequestThrowsException("postData", "{data: false, error: true}", new \Exception);
        $result = $this->apiclientInstance->sendProducts($this::TEST_PRODUCT_ID);
        $this->assertEquals("{data: false, error: true}", $result);
    }

    public function testSendingLogsReturnsResponse()
    {
        $this->assertDataForRequestIsCalled("getLogsURL");
        $this->mockHttpRequestReturnsApprovedResponse("postData", "{}");
        $result = $this->apiclientInstance->sendLogs([]);
        $this->assertEquals("{}", $result);
    }

    public function testSendingLogsExceptionReturnsResponse()
    {
        $this->mockHttpRequestThrowsException("postData", "{}", new \Exception);
        $result = $this->apiclientInstance->sendLogs($this::TEST_PRODUCT_ID);
        $this->assertEquals("{}", $result);
    }

    public function testSendingCredentialsCheckReturnsResponse()
    {
        $this->assertDataForRequestIsCalled("getCheckCredentialsURL");
        $this->mockHttpRequestReturnsApprovedResponse("postData", "{}");
        $result = $this->apiclientInstance->sendCheckCredentials([]);
        $this->assertEquals("{}", $result);
    }

    public function testSendingCredentialsCheckExceptionReturnsResponse()
    {
        $this->mockHttpRequestThrowsException("postData", false, new \Exception);
        $result = $this->apiclientInstance->sendCheckCredentials($this::TEST_PRODUCT_ID);
        $this->assertEquals(false, $result);
    }

    /**
     * @param string $method
     * @param mixed $returns
     * @param \Exception $exception
     */
    private function mockHttpRequestThrowsException($method, $returns, $exception)
    {
        $this->mockHttpRequest
            ->expects($this->once())
            ->method($method)
            ->willReturn($returns)
            ->willThrowException($exception);
    }

    /**
     * @param string $method
     * @param mixed $returns
     * @return mixed
     */
    private function mockHttpRequestReturnsApprovedResponse($method, $returns)
    {
        return $this->mockHttpRequest->method($method)->willReturn($returns);
    }

    private function assertPropertiesMethodsAreCalledOnce()
    {
        $this->mockEnvironment->expects($this->once())->method("identifyCustomer")->willReturn([
            'session_id' => self::TEST_SESSION_ID,
            'visitor_id' => self::TEST_VISITOR_ID,
            'customer_id' => self::TEST_CUSTOMER_ID
        ]);
        $this->mockEnvironment->expects($this->once())->method("getWebsite")->willReturn($this::TEST_WEBSITE);
        $this->mockEnvironment->expects($this->once())->method("getStoreCode")->willReturn($this::TEST_STORE_CODE);
        $this->mockEnvironment->expects($this->once())->method("getUserAgent")->willReturn($this::TEST_USER_AGENT);
        $this->mockEnvironment->expects($this->once())->method("getLang")->willReturn($this::TEST_LANG);
        $this->mockEnvironment->expects($this->once())->method("getCurrencyCode")->willReturn($this::TEST_CURRENCY);
        $this->mockEnvironment->expects($this->once())->method("getVersion")->willReturn($this::TEST_VERSION);
        $this->mockEnvironment->expects($this->once())->method("getLocaleCode")->willReturn($this::TEST_LANG);
        $this->mockCookie->expects($this->once())->method("isUserNotAllowSaveCookie")->willReturn(true);
    }

    private function assertDataForRequestIsCalled($eventName)
    {
        $this->mockEnvironment->expects($this->once())->method($eventName)->willReturn("test_api_end_point");
        $this->mockEnvironment->expects($this->once())->method("getTimeout")->willReturn($this::TEST_TIMEOUT);
    }
}
