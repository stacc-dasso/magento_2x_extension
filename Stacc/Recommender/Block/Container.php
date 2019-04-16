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
    protected $environment;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var
     */
    private $product;

    /**
     * Default Template for recommendations
     *
     * @var string
     */
    private $defaultRecommendationTemplate = 'Stacc_Recommender::recommendations.phtml';

    /**
     * Default value for element id, if id is not set
     *
     * @var string
     */
    private $defaultElementId = 'stacc_product_default';

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
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->environment = $environment;
        $this->logger = $logger;
        $this->registry = $registry;
    }

    /**
     * Returns Container Element ID
     *
     * @return string
     */
    public function getElementId()
    {
        return $this->getData("elementId") ?: $this->defaultElementId;
    }

    /**
     * Returns Template file name
     *
     * @return string
     */
    public function getRecommendationTemplate()
    {
        return $this->getData("recommendationTemplate") ?: $this->defaultRecommendationTemplate;
    }

    /**
     * @return string
     */
    public function getProductId()
    {
        try {
            $product = $this->getProduct();
            if (!($product===null)) {
                return $product->getId();
            }
            return "";
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Block/Container.php->getExtensionVersion() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
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
            return $this->environment->getVersion();
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Block/Container.php->getExtensionVersion() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            return null;
        }
    }

    /**
     * @return mixed|null
     */
    private function getProduct()
    {
        if (($this->product === null)) {
            $this->product = $this->registry->registry('product');

            if (($this->product === null) || !$this->product->getId()) {
                return null;
            }
        }

        return $this->product;
    }
}
