<?php

namespace Stacc\Recommender\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Stacc\Recommender\Network\Apiclient;
use Stacc\Recommender\Logger\Logger;

/**
 * Class Recommendations
 * @package Stacc\Recommender\Model
 */
class Recommendations extends AbstractModel
{

    /**
     * @var Apiclient
     */
    protected $_apiclient;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * Recommendations constructor.
     * @param Apiclient $apiclient
     * @param Logger $logger
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(Apiclient $apiclient, Logger $logger, CollectionFactory $collectionFactory, Context $context, Registry $registry, AbstractResource $resource = null, AbstractDb $resourceCollection = null, array $data = [])
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_apiclient = $apiclient;
        $this->_logger = $logger;
        $this->_productCollectionFactory = $collectionFactory;
    }

    /**
     * @param $productId
     * @param $blockId
     * @return array
     */
    public function getRecommendations($productId, $blockId)
    {
        $recommendations = array();

        try {
            $productIds = $this->_apiclient->askRecommendations($productId, $blockId);

            $collection = $this->_productCollectionFactory->create()
                ->addAttributeToFilter('entity_id', array('in' => $productIds))
                ->addAttributeToSelect('*')
                ->addFinalPrice();

            foreach ($productIds as $productId) {
                if ($product = $collection->getItemById($productId)) {
                    $recommendations[] = $product;
                }
            }

        } catch (\Exception $exception) {
            $this->_logger->critical("Model/Recommendations.php->getRecommendations() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }

        return $recommendations;
    }
}