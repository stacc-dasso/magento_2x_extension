<?php

namespace Stacc\Recommender\Observer;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Stacc\Recommender\Logger\Logger;
use Stacc\Recommender\Network\Apiclient;
use Stacc\Recommender\Network\Environment;

/**
 * Class Purchase
 * @package Stacc\Recommender\Observer
 */
class Purchase implements ObserverInterface
{
    /**
     * @var Apiclient
     */
    protected $_apiClient;

    /**
     * @var Environment
     */
    protected $_environment;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var Logger
     */
    protected $_logger;


    /**
     * Purchase constructor.
     * @param Apiclient $_apiClient
     * @param Environment $_environment
     * @param StoreManagerInterface $_storeManager
     * @param CheckoutSession $_checkoutSession
     * @param Logger $_logger
     */
    public function __construct(Apiclient $_apiClient, Environment $_environment, StoreManagerInterface $_storeManager, CheckoutSession $_checkoutSession, Logger $_logger)
    {
        $this->_apiClient = $_apiClient;
        $this->_environment = $_environment;
        $this->_storeManager = $_storeManager;
        $this->_checkoutSession = $_checkoutSession;
        $this->_logger = $_logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {

        try {
            $response = $this->_apiClient->sendPurchaseEvent($this->getPurchases());

            if (!$response) {
                $this->_logger->error("Failed to sync purchase event", array($response));
            }
        } catch (\Exception $exception) {
            $this->_logger->critical("Observer/Purchase->execute() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }
    }

    /**
     * Helper function. Sets data to be sent to STACC on purchase event.
     * @return array
     */
    private function getPurchases()
    {
        try {
            $items = array();
            $collection = $this->_checkoutSession->getLastRealOrder()->getAllVisibleItems();
            $store = $this->_storeManager->getStore();
            foreach ($collection as $product) {
                $prod = [
                    'item_id' => $product->getProductId(),
                    'quantity' => $product->getQtyOrdered(),
                    'price' => $product->getPrice(),
                    'properties' => [
                        'formatted_price' => $product->getRowTotal() - $product->getDiscountAmount() + $product->getTaxAmount(),
                        'sku' => $product->getSku(),
                        'tax_amount' => $product->getTaxAmount(),
                        'currency' => $this->_environment->getCurrencyCode(),
                        'current_crcy' => $store->getCurrentCurrencyCode(),
                        'lang' => $store->getCode()
                    ]
                ];
                $items[] = $prod;
            }

            return $items;
        } catch (\Exception $exception) {
            $this->_logger->critical("Observer/Purchase->getPurchases() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return array();
        }
    }
}