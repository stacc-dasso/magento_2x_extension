<?php

namespace Stacc\Recommender\Controller\Recommendation;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Stacc\Recommender\Logger\Logger;
use Stacc\Recommender\Network\Environment;

/**
 * Class Stores
 * @package Stacc\Recommender\Controller\Recommendation
 */
class Stores extends Action
{
    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * Stores constructor.
     * @param Environment $environment
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param LayoutInterface $layout
     * @param Context $context
     */
    public function __construct(
        Environment $environment,
        StoreManagerInterface $storeManager,
        Logger $logger,
        LayoutInterface $layout,
        Context $context
    ) {
        parent::__construct($context);

        $this->environment = $environment;
        $this->storeManager = $storeManager;
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
            if ($this->authApi($url_hash)) {
                $this->getResponse()->setBody(json_encode($this->getStoreData()));
            } else {
                $this->logger->error("Failed to authenticate the request");
                $this->getResponse()->setBody("");
            }
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Controller/Recommendation/Stores.php->execute() Exception: ",
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
                    "Controller/Recommendation/Stores.php->auth_api() Exception: ",
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
     * @return array
     */
    protected function getStoreData()
    {
        try {
            $timestamp = $this->getRequest()->getParam('t');
            $stores = $this->storeManager->getStores();
            $storeData = ["timestamp" => $timestamp];
            foreach (array_keys($stores) as $storeId) {
                $store = $this->storeManager->getStore($storeId);
                $storeData[$storeId] = $this->mapStoreInfo($storeId, $store);
            }
            return $storeData;
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Controller/Recommendation/Stores.php->generateStoreData() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            return [];
        }
    }

    /**
     * @param $storeId
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @return array
     */
    protected function mapStoreInfo($storeId, \Magento\Store\Api\Data\StoreInterface $store)
    {
        $storeInfo = [];
        $storeInfo["id"] = $storeId;
        $storeInfo["name"] = $store->getName();
        $storeInfo["storeInUrl"] = $store->getStoreInUrl();
        $storeInfo['store_data'] = $store->getData();
        $storeInfo["website"] = [$store->getWebsite()->getId() => $store->getWebsite()->getData()];
        $storeInfo["group"] = [
            $store->getGroup()->getId() => [
                "name" => $store->getGroup()->getName(),
                "id" => $store->getGroup()->getId(),
                "data" => $store->getGroup()->getData()
            ]
        ];
        return $storeInfo;
    }
}
