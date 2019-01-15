<?php

namespace Stacc\Recommender\Block;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Checkout\Model\Session;
use Stacc\Recommender\Model\RecommendationsFactory;
use Stacc\Recommender\Model\Collection;
use Magento\Catalog\Block\Product\Context;
use Magento\Checkout\Model\ResourceModel\Cart;
use Magento\Framework\Module\Manager;
use Stacc\Recommender\Network\Environment;
use Magento\Framework\Phrase;


/**
 * Class Recommendation
 * @package Stacc\Recommender\Block
 */
class Recommendation extends \Magento\Catalog\Block\Product\ProductList\Upsell
{

    /**
     * @var Recommendations
     */
    protected $_recommendationsFactory;

    /**
     * @var Environment
     */
    protected $_environment;

    /**
     * @var Collection
     */
    protected $_collection;

    /**
     * @var
     */
    protected $_timestamp;

    /**
     * Recommendation constructor.
     * @param RecommendationsFactory $recommendationsFactory
     * @param Environment $environment
     * @param Collection $collection
     * @param Context $context
     * @param Cart $checkoutCart
     * @param Visibility $catalogProductVisibility
     * @param Session $checkoutSession
     * @param Manager $moduleManager
     */
    public function __construct(
        RecommendationsFactory $recommendationsFactory,
        Environment $environment,
        Collection $collection,
        Context $context,
        Cart $checkoutCart,
        Visibility $catalogProductVisibility,
        Session $checkoutSession,
        Manager $moduleManager
    )
    {
        parent::__construct($context, $checkoutCart, $catalogProductVisibility, $checkoutSession, $moduleManager);

        $this->_recommendationsFactory = $recommendationsFactory;
        $this->_environment = $environment;
        $this->_collection = $collection;
    }


    /**
     * @return $this|\Magento\Catalog\Block\Product\ProductList\Upsell
     */
    protected function _prepareData()
    {
        try {
            $recommendationModel = $this->_recommendationsFactory->create();
            $recommendations = $recommendationModel->getRecommendations($this->getProductId(), $this->getBlockId());
            $this->_itemCollection = $this->_collection;
            if (!empty($recommendations)) {
                foreach ($recommendations as $row) {
                    if (!$this->_collection->getItemById($row->getId())) {
                        $this->_itemCollection->addItem($row);
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->_logger->critical("Block/Recommendation.php->_prepareData() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }
        return $this;
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getRecommendations()
    {
        return $this->_itemCollection;
    }

    /**
     * @return null|string
     */
    public function getLocaleCode()
    {
        return $this->_environment->getLocaleCode();
    }

    /**
     * @return int|null
     */
    public function getCacheLifetime()
    {
        return null;
    }

    /**
     * Returns timestamp of block
     *
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->_timestamp;
    }

    /**
     * Sets timestamp for block
     *
     * @param $timestamp
     * @return $this
     */
    public function setTimestamp($timestamp)
    {
        $this->_timestamp = $timestamp;
        return $this;
    }


    /**
     * @return Phrase
     */
    public function getTitle()
    {
        return new Phrase('Our recommended products');
    }

    /**
     * @return string
     */
    public function getType()
    {
        if ($this->getTemplate() != "Stacc_Recommender::recommendations.phtml") {
            return "upsell";
        } else{
            return 'recommendation';
        }
    }
}