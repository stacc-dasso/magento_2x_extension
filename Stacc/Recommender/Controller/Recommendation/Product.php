<?php

namespace Stacc\Recommender\Controller\Recommendation;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Stacc\Recommender\Logger\Logger;
use Stacc\Recommender\Network\Environment;
use Stacc\Recommender\Model\SyncFactory;

/**
 * Class Product
 * @package Stacc\Recommender\Controller\Recommendation
 */
class Product extends Action
{
    /**
     * For product id verification to run on enterd id
     */
    const TYPE_PRODUCT = "product";

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var SyncFactory
     */
    protected $syncFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;
    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * Product constructor.
     * @param Environment $environment
     * @param SyncFactory $syncFactory
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     * @param Logger $logger
     * @param LayoutInterface $layout
     * @param Context $context
     */
    public function __construct(
        Environment $environment,
        SyncFactory $syncFactory,
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        Logger $logger,
        LayoutInterface $layout,
        Context $context
    ) {
        parent::__construct($context);

        $this->environment = $environment;
        $this->syncFactory = $syncFactory;
        $this->storeManager = $storeManager;
        $this->productCollectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->layout = $layout;
        $this->layout->getUpdate()->addHandle('default');
    }

    /**
     * Receives view events and executes Observer
     */
    public function execute()
    {
        try {
            $url_hash = (string)$this->getRequest()->getParam('h');
            $timestamp = $this->getRequest()->getParam('t');
            $productId = $this->verifyId($this->getRequest()->getParam('p'), $this::TYPE_PRODUCT);

            if ($this->authApi($url_hash)) {
                $response = [
                    "product" => [],
                    "timestamp" => $timestamp
                ];

                if ($productId) {
                    $collection = $this->productCollectionFactory
                        ->create()
                        ->addAttributeToSelect('*')
                        ->addAttributeToFilter('entity_id', ['in' => $productId]);
                    $syncModel = $this->syncFactory->create();
                    $bulk = $syncModel->getModifiedProductsAsBulk($collection);
                } else {
                    $bulk = [];
                }

                $response["product"] = $bulk;

                $this->getResponse()->setBody(json_encode($response));
            } else {
                $this->logger->error("Failed to authenticate the request");
                $this->getResponse()->setBody("");
            }
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Controller/Recommendation/Product.php->execute() Exception: ",
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
     * Method that checks the hash of url
     *
     * @param $hash
     * @return bool
     */
    private function authApi($hash)
    {
        try {
            $mainHash = hash("sha256", $this->environment->getShopId() . $this->environment->getApiKey());

            return $mainHash == $hash;
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Controller/Recommendation/Product.php->auth_api() Exception: ",
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
     * @param $id
     * @param string $type
     * @return null
     *
     * Verify product Id
     */
    private function verifyId($id, $type = "")
    {
        try {
            if (isset($id)) {
                if ($type == $this::TYPE_PRODUCT) {
                    $product = $this->productCollectionFactory
                        ->create()
                        ->addAttributeToSelect("sku")
                        ->addAttributeToFilter('entity_id', ['in' => $id]);
                    if ((int)$id && $product) {
                        return $id;
                    }
                } else {
                    if ((int)$id) {
                        return $id;
                    }
                    return null;
                }
            }
            return null;
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Controller/Recommendation/Product.php->verify_id() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            return null;
        }
    }
}
