<?php

namespace Stacc\Recommender\Network;

use Magento\Customer\Model\Visitor;
use Magento\Framework\App\Config as ScopeConfigInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\TestCase;
use Stacc\Recommender\Logger\Logger;

class EnvironmentTest extends TestCase
{
    const TEST_USER_AGENT = "test_user_agent";
    const TEST_URL = 'https://test.url/api/v2';
    const TEST_SESSION_ID = 'stacc_test_session';
    const TEST_VISITOR_ID = 'stacc_test_visitor';
    const TEST_CUSTOMER_ID = 'stacc_test_customer';
    const TEST_SHOP_ID = "test_shop_id";
    const TEST_API_KEY = "test_api_key";
    const TEST_CURRENCY = 'USD';
    const TEST_TIMEOUT = 3000;
    const TEST_LANG = 'Default';
    const TEST_LOCALE = 'EN_US';

    private $mockStoreManager;
    private $mockComponentRegistrar;
    private $mockCustomerVisitor;
    private $mockScopeConfig;
    private $mockResolver;
    private $mockHeader;
    private $mockLogger;
    private $environmentInstance;

    protected function setUp()
    {
        $this->mockStoreManager = $this->createMock(StoreManager::class);
        $store = $this->createMock(Store::class);
        $currencyMock = $this->createMock(WebsiteInterface::class);
        $store->method("getCurrentCurrency")->willReturn($currencyMock);
        $store->method("getCode")->willReturn(self::TEST_LANG);
        $currencyMock->method("getCode")->willReturn(self::TEST_CURRENCY);
        $this->mockStoreManager->method("getStore")->willReturn($store);
        $this->mockComponentRegistrar = $this->createMock(ComponentRegistrar::class);
        $this->mockComponentRegistrar->expects($this->once())->method("getPath")->willReturn("../../..");
        $this->mockCustomerVisitor = $this->createMock(Visitor::class);

        $this->mockScopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();

        $this->mockResolver = $this->createMock(Resolver::class);
        $this->mockHeader = $this->createMock(Header::class);
        $this->mockLogger = $this->createMock(Logger::class);
        $this->environmentInstance = new Environment(
            $this->mockComponentRegistrar,
            $this->mockStoreManager,
            $this->mockCustomerVisitor,
            $this->mockScopeConfig,
            $this->mockResolver,
            $this->mockHeader,
            $this->mockLogger
        );
    }

    /**
     * @dataProvider endpointProvider
     * @param $value
     * @param $expected
     */
    public function testEndPointReturnsCorrectValue($value, $expected)
    {
        $result = $this->environmentInstance->getEndpoint($value);
        $this->assertEquals($expected, $result);
    }

    public function testIdentifyCustomerReturnsValue()
    {
        $this->mockCustomerVisitor->expects($this->at(0))->method("getData")->with("session_id", null)->willReturn($this::TEST_SESSION_ID);
        $this->mockCustomerVisitor->expects($this->at(1))->method("getData")->with("visitor_id", null)->willReturn($this::TEST_VISITOR_ID);
        $this->mockCustomerVisitor->expects($this->at(2))->method("getData")->with("customer_id", null)->willReturn($this::TEST_CUSTOMER_ID);
        $result = $this->environmentInstance->identifyCustomer();
        $this->assertEquals([
            'session_id' => self::TEST_SESSION_ID,
            'visitor_id' => self::TEST_VISITOR_ID,
            'customer_id' => self::TEST_CUSTOMER_ID
        ], $result);
    }

    public function testIdentifyCustomerReturnsValueIfExceptionThrown()
    {
        $this->mockCustomerVisitor->expects($this->at(0))->method("getData")->with("session_id", null)->willReturn($this::TEST_SESSION_ID)->willThrowException(new \Exception);
        $this->mockLogger->expects($this->once())->method("critical");
        $result = $this->environmentInstance->identifyCustomer();
        $this->assertEquals([], $result);
    }

    public function testCurrencyCodeIsReturned()
    {
        $result = $this->environmentInstance->getCurrencyCode();
        $this->assertEquals(self::TEST_CURRENCY, $result);
    }

    public function testCurrencyCodeReturnsValueWhenExceptionIsThrown()
    {
        $store = $this->createMock(Store::class);
        $store->method("getCurrentCurrency")->willReturn($store);
        $store->method("getCode")->willReturn(self::TEST_CURRENCY)->willThrowException(new \Exception);
        $this->mockStoreManager->method("getStore")->willReturn($store);
        $result = $this->environmentInstance->getCurrencyCode();
        $this->assertEquals(self::TEST_CURRENCY, $result);
    }

    public function testGetApiUrlReturnsValue()
    {
        $result = $this->environmentInstance->getApiUrl();
        $this->assertRegExp("/http[s]?\:\/\/(recommender\.stacc\.cloud|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/api\/v2/", $result);
    }

    public function testGetM2UrlReturnsValue()
    {
        $result = $this->environmentInstance->getM2Url();
        $this->assertRegExp("/http[s]?\:\/\/(recommender\.stacc\.cloud|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/api\/magento\/2x/", $result);
    }

    /**
     * @dataProvider apiEndpointMethodProvider
     * @param $method
     * @param $urlRegExp
     */
    public function testApiMethodReturnsCorrectUrl($method, $urlRegExp)
    {
        $result = call_user_func(array($this->environmentInstance, $method));
        $this->assertRegExp("/http[s]?\:\/\/(recommender\.stacc\.cloud|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/api\/v2" . $urlRegExp . "/", $result);
    }

    /**
     * @dataProvider apiM1xEndpointMethodProvider
     * @param $method
     * @param $urlRegExp
     */
    public function testApiM1xMethodReturnsCorrectUrl($method, $urlRegExp)
    {
        $result = call_user_func(array($this->environmentInstance, $method));
        $this->assertRegExp("/http[s]?\:\/\/(recommender\.stacc\.cloud|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/api\/magento\/2x" . $urlRegExp . "/", $result);
    }

    public function testApiKeyReturnedIsValid()
    {

        $this->mockScopeConfig->expects($this->once())->method("getValue")->with("stacc_recommender/configuration/stacc_api_key", \Magento\Store\Model\ScopeInterface::SCOPE_STORE)->willReturn(self::TEST_API_KEY);
        $result = $this->environmentInstance->getApiKey();
        $this->assertEquals(self::TEST_API_KEY, $result);
    }

    public function testApiKeyReturnedValueIfExceptionThrown()
    {

        $this->mockScopeConfig->expects($this->once())->method("getValue")->willReturn(self::TEST_API_KEY)->willThrowException(new \Exception);

        $this->mockLogger->expects($this->once())->method("critical");
        $result = $this->environmentInstance->getApiKey();
        $this->assertEquals("", $result);
    }

    public function testShopIdReturnedIsValid()
    {
        $this->mockScopeConfig->expects($this->once())->method("getValue")->with('stacc_recommender/configuration/stacc_shop_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)->willReturn(self::TEST_SHOP_ID);
        $result = $this->environmentInstance->getShopId();
        $this->assertEquals(self::TEST_SHOP_ID, $result);
    }

    public function testShopIdValueIfExceptionThrown()
    {
        $this->mockScopeConfig->expects($this->once())->method("getValue")->willThrowException(new \Exception);

        $this->mockLogger->expects($this->once())->method("critical");
        $result = $this->environmentInstance->getShopId();
        $this->assertEquals("", $result);
    }


    public function testGetCredentialsReturnsValue()
    {
        $this->mockScopeConfig->expects($this->at(0))->method("getValue")->with('stacc_recommender/configuration/stacc_shop_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)->willReturn(self::TEST_SHOP_ID);
        $this->mockScopeConfig->expects($this->at(1))->method("getValue")->with("stacc_recommender/configuration/stacc_api_key", \Magento\Store\Model\ScopeInterface::SCOPE_STORE)->willReturn(self::TEST_API_KEY);

        $result = $this->environmentInstance->getCredentials();
        $this->assertEquals([
            "id" => self::TEST_SHOP_ID,
            "key" => self::TEST_API_KEY
        ], $result);
    }

    public function testGetVersionReturnsCorrectFormatVersion()
    {
        $result = $this->environmentInstance->getVersion();
        $this->assertRegExp("/^[0-9]?[0-9]\.[0-9]?[0-9]\.[0-9]?[0-9]$/", $result);
    }

    public function testGetLanguageReturnsExpectedValue()
    {
        $result = $this->environmentInstance->getLang();
        $this->assertEquals(self::TEST_LANG, $result);
    }

    public function testGetWebsiteReturnsExpectedValue()
    {
        $website = $this->createMock(WebsiteInterface::class);
        $website->method("getName")->willReturn(self::TEST_URL);
        $this->mockStoreManager->method("getWebsite")->willReturn($website);
        $result = $this->environmentInstance->getWebsite();
        $this->assertEquals(self::TEST_URL, $result);
    }

    public function testGetWebsiteReturnsValueOnException()
    {
        $website = $this->createMock(WebsiteInterface::class);
        $website->method("getName")->willReturn(self::TEST_URL);
        $this->mockStoreManager->method("getWebsite")->willReturn($website)->willThrowException(new \Exception);

        $this->mockLogger->expects($this->once())->method("critical");
        $result = $this->environmentInstance->getWebsite();
        $this->assertEquals("", $result);
    }

    public function testGetStoreReturnsCorrectInstance()
    {
        $result = $this->environmentInstance->getStore();
        $this->assertInstanceOf(Store::class, $result);
    }

    public function testGetStoreReturnsNullOnException()
    {

        $this->mockStoreManager->method("getStore")->willReturn($this->createMock(Store::class))->willThrowException(new \Exception);
        $this->mockLogger->expects($this->once())->method("critical");
        $result = $this->environmentInstance->getStore();
        $this->assertEquals(null, $result);
    }

    public function testGetStoreCodeReturnsValue()
    {
        $result = $this->environmentInstance->getStoreCode();
        $this->assertEquals(self::TEST_LANG, $result);
    }

    public function testGetLocaleCodeReturnsValue()
    {
        $this->mockResolver->expects($this->once())->method("getLocale")->willReturn(self::TEST_LOCALE);
        $result = $this->environmentInstance->getLocaleCode();
        $this->assertEquals(self::TEST_LOCALE, $result);

    }

    public function testGetLocaleCodeReturnsValueOnException()
    {
        $this->mockResolver->expects($this->once())->method("getLocale")->willReturn(self::TEST_LOCALE)->willThrowException(new \Exception);
        $this->mockLogger->expects($this->once())->method("critical");
        $result = $this->environmentInstance->getLocaleCode();
        $this->assertEquals("", $result);
    }

    public function testGetTimeOutReturnsValue()
    {
        $result = $this->environmentInstance->getTimeout();
        $this->assertEquals(self::TEST_TIMEOUT, $result);
    }

    public function testGetUserAgentReturnsValue()
    {
        $this->mockHeader->method("getHttpUserAgent")->willReturn(self::TEST_USER_AGENT);
        $result = $this->environmentInstance->getUserAgent();
        $this->assertEquals(self::TEST_USER_AGENT, $result);
    }

    public function apiEndpointMethodProvider()
    {
        return [
            ['getAddToCartEventURL', "\/send_add_to_cart"],
            ['getCatalogSyncURL', "\/catalog_sync"],
            ['getRecommendationsURL', "\/get_recs"],
            ['getPurchaseEventURL', "\/send_purchase"],
            ['getViewEventURL', "\/send_view"],
            ['getLogsURL', "\/send_logs"],
            ['getSearchEventURL', "\/send_search"]
        ];
    }

    public function apiM1xEndpointMethodProvider()
    {
        return [
            ['getCheckCredentialsURL', "\/check_credentials"]
        ];
    }

    public function endpointProvider()
    {
        return [
            ['add_to_cart', "/send_add_to_cart"],
            ['catalog_sync', "/catalog_sync"],
            ['get_recs', "/get_recs"],
            ['purchase', "/send_purchase"],
            ['view', "/send_view"],
            ['logs', "/send_logs"],
            ['search', "/send_search"],
            ['check', "/check_credentials"]
        ];
    }
}