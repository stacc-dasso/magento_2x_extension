<?php
namespace Stacc\Recommender\Controller\Recommendation;

use Magento\TestFramework\TestCase\AbstractController;
use Stacc\Recommender\Network\Environment;

class ProductIntegrationTest extends AbstractController
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
    public function testRendersSuccessfulSyncResponse()
    {
        $time = time();
        $this->dispatch('recommender/recommendation/product?h='.hash("sha256", $this::TEST_SHOP_ID.$this::TEST_API_KEY)."&t=".$time);
        $string = $this->getResponse()->getBody();
        $this->assertJson($string);
        $this->assertRegExp("/".$time."/", $string);
    }

    public function testDisplaysEmptyWhenAccessingWithPartialCredentials()
    {
        $this->dispatch('recommender/recommendation/product?h='.hash("sha256", $this::TEST_API_KEY));
        $this->assertEquals("", $this->getResponse()->getBody());
        $this->dispatch('recommender/recommendation/product?h='.hash("sha256", $this::TEST_SHOP_ID));
        $this->assertEquals("", $this->getResponse()->getBody());
    }
}
