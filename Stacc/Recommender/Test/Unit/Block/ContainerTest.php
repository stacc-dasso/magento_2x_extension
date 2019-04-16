<?php

namespace Stacc\Recommender\Block;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Stacc\Recommender\Logger\Logger;
use Stacc\Recommender\Network\Environment;

class ContainerTest extends \PHPUnit\Framework\TestCase
{
    const TEST_DEFAULT_TEMPLATE = 'Stacc_Recommender::recommendations.phtml';
    const TEST_DEFAULT_ELEMENT_ID = 'stacc_product_default';
    private $mockEnvironment;
    private $mockRegistry;
    private $mockContext;
    private $containerInstance;
    private $objectManager;
    private $mockLogger;

    protected function setUp()
    {
        $this->mockEnvironment = $this->createMock(Environment::class);
        $this->mockLogger = $this->createMock(Logger::class);
        $this->mockContext = $this->createMock(Context::class);
        $this->mockRegistry = $this->createMock(Registry::class);
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->containerInstance = new Container(
            $this->mockEnvironment,
            $this->mockLogger,
            $this->mockRegistry,
            $this->mockContext
        );
    }

    public function testGetElementIdReturnsValue()
    {
        $result = $this->containerInstance->getElementId();

        $this->assertEquals(self::TEST_DEFAULT_ELEMENT_ID, $result);
        $this->assertNotEmpty($result);
    }

    public function testGetRecommendationTemplateReturnsValue()
    {
        $result = $this->containerInstance->getRecommendationTemplate();

        $this->assertEquals(self::TEST_DEFAULT_TEMPLATE, $result);
        $this->assertNotEmpty($result);
    }

    public function testProductId()
    {
        $product = $this->objectManager->getObject('Magento\Catalog\Model\Product');
        $testProductId = 12;
        $product
            ->setId(12)
            ->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
            ->setWebsiteIds([1])
            ->setName('Simple Product 1')
            ->setSku('simple1')
            ->setPrice(10)
            ->setDescription('Description')
            ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
            ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
            ->setUrlKey('url-key')
            ->save();
        $this->mockRegistry->expects($this->once())->method("registry")->with("product")->willReturn($product);
        $result = $this->containerInstance->getProductId();
        $this->assertEquals($testProductId, $result);
    }

    public function testProductIdWillReturnValueOnException()
    {
        $this->mockRegistry
            ->expects($this->once())
            ->method("registry")
            ->with("product")
            ->willReturn("")
            ->willThrowException(new \Exception);

        $this->mockLogger->expects($this->once())->method("critical");
        $result = $this->containerInstance->getProductId();
        $this->assertEquals("", $result);
    }

    public function testGetExtensionVersion()
    {
        $this->mockEnvironment->expects($this->once())->method("getVersion")->willReturn("2.0.0");
        $result = $this->containerInstance->getExtensionVersion();
        $this->assertEquals("2.0.0", $result);
    }

    public function testGetExtensionVersionReturnsNullOnException()
    {
        $this->mockEnvironment
            ->expects($this->once())
            ->method("getVersion")
            ->willReturn("2.0.0")
            ->willThrowException(new \Exception);
        $this->mockLogger->expects($this->once())->method("critical")->willReturn("");
        $result = $this->containerInstance->getExtensionVersion();
        $this->assertNull($result);
    }
}
