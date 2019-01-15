<?php

namespace Stacc\Recommender\Controller\Recommendation;

use Magento\TestFramework\TestCase\AbstractController;
use Stacc\Recommender\Network\Apiclient;

class GetIntegrationTest extends AbstractController
{
    public function setUp()
    {
        parent::setUp();
        $apiClient = $this->getMockBuilder(Apiclient::class)->disableOriginalConstructor()->getMock();
        $apiClient->method('askRecommendations')->willReturn([1, 2]);
        $this->_objectManager->addSharedInstance($apiClient, Apiclient::class);

        $product = $this->_objectManager->create('Magento\Catalog\Model\Product');
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

        $product = $this->_objectManager->create('Magento\Catalog\Model\Product');
        $product
            ->setId(13)
            ->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
            ->setWebsiteIds([1])
            ->setName('Simple Product 2')
            ->setSku('simple2')
            ->setPrice(10)
            ->setDescription('Description')
            ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
            ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
            ->setUrlKey('url-key2')
            ->save();
    }

    public function testRendersRecommendations()
    {
        $this->dispatch('recommender/recommendation/get?productId=1&blockId=stacc_product_default&template=Stacc_Recommender%3A%3Arecommendations.phtml&tjs=' . time());
        $this->assertContains('data-product-id="1"', $this->getResponse()->getBody());
        $this->assertContains('data-product-id="2"', $this->getResponse()->getBody());
    }

}