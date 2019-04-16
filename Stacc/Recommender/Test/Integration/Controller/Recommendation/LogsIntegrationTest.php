<?php

namespace Stacc\Recommender\Controller\Recommendation;

use Magento\TestFramework\TestCase\AbstractController;
use Stacc\Recommender\Network\Environment;

class LogsIntegrationTest extends AbstractController
{
    const TEST_SHOP_ID = "test_magento_2";
    const TEST_API_KEY = "12dcac9d156ac9d9a9fdbf6096cf1c42";

    public function setUp()
    {
        parent::setUp();
        $environment = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
        $environment->method("getApiKey")->willReturn($this::TEST_API_KEY);
        $environment->method("getShopId")->willReturn($this::TEST_SHOP_ID);
        $this->_objectManager->addSharedInstance($environment, Environment::class);
    }

    public function testRendersSuccessfulSendingLogsResponse()
    {
        $time = time();
        $this->dispatch('recommender/recommendation/logs?h=' . hash("sha256", $this::TEST_SHOP_ID . $this::TEST_API_KEY) . "&t=" . $time);
        $string = $this->getResponse()->getBody();
        $this->assertRegExp("/" . $time . "/", $string);
        $stringTimeAndSyncArray = explode(" ", $string);
        if (strpos($string, "/") !== false) {
            $stringArray = explode("/", $stringTimeAndSyncArray[1]);
            $this->assertContains('/', $string);
            $this->assertEquals($stringArray[1], $stringArray[0]);
        }
    }

    public function testDisplaysEmptyWhenAccessingWithPartialCredentials()
    {
        $this->dispatch('recommender/recommendation/logs?h=' . hash("sha256", $this::TEST_API_KEY));
        $this->assertEquals("", $this->getResponse()->getBody());
        $this->dispatch('recommender/recommendation/logs?h=' . hash("sha256", $this::TEST_SHOP_ID));
        $this->assertEquals("", $this->getResponse()->getBody());
    }
}
