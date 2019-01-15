<?php

namespace Stacc\Recommender\Block;

use Magento\Framework\View\Element\Template\Context;
use Stacc\Recommender\Logger\Logger;
use Stacc\Recommender\Network\Environment;
use Magento\Framework\Registry;

/**
 * Class Container
 * @package Stacc\Recommender\Block
 */
class Container extends \Magento\Framework\View\Element\Template
{

    /**
     * @var Environment
     */
    protected $_environment;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var
     */
    private $_product;

    /**
     * Default Template for recommendations
     *
     * @var string
     */
    private $_defaultRecommendationTemplate = 'Stacc_Recommender::recommendations.phtml';

    /**
     * Default value for element id, if id is not set
     *
     * @var string
     */
    private $_defaultElementId = 'stacc_product_default';

    /**
     * Container constructor.
     * @param Environment $environment
     * @param Logger $logger
     * @param Registry $registry
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Environment $environment,
        Logger $logger,
        Registry $registry,
        Context $context,
        array $data = array()
    )
    {
        parent::__construct($context, $data);

        $this->_environment = $environment;
        $this->_logger = $logger;
        $this->_registry = $registry;
    }

    /**
     * Returns Container Element ID
     *
     * @return string
     */
    public function getElementId()
    {
        return $this->getData("elementId") ?: $this->_defaultElementId;
    }

    /**
     * Returns Template file name
     *
     * @return string
     */
    public function getRecommendationTemplate()
    {
        return $this->getData("recommendationTemplate") ?: $this->_defaultRecommendationTemplate;
    }

    /**
     * @return string
     */
    public function getProductId()
    {
        try {
            $product = $this->getProduct();
            if (!is_null($product)) {
                return $product->getId();
            }
            return "";
        } catch (\Exception $exception) {
            $this->_logger->critical("Block/Container.php->getExtensionVersion() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "";
        }
    }

    /**
     * Returns extension version to for the block
     *
     * @return string
     */
    public function getExtensionVersion()
    {
        try {
            return $this->_environment->getVersion();
        } catch (\Exception $exception) {
            $this->_logger->critical("Block/Container.php->getExtensionVersion() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }

    /**
     * @return mixed|null
     */
    private function getProduct()
    {
        if (is_null($this->_product)) {
            $this->_product = $this->_registry->registry('product');

            if (is_null($this->_product) || !$this->_product->getId()) {
                return null;
            }
        }

        return $this->_product;
    }
}