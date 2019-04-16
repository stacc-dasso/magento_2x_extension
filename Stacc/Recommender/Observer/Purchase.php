<?php

namespace Stacc\Recommender\Observer;

use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
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
    protected $apiclient;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Purchase constructor.
     * @param Apiclient $apiclient
     * @param Environment $environment
     * @param StoreManagerInterface $storeManager
     * @param CheckoutSession $checkoutSession
     * @param Logger $logger
     */
    public function __construct(
        Apiclient $apiclient,
        Environment $environment,
        StoreManagerInterface $storeManager,
        CheckoutSession $checkoutSession,
        Logger $logger
    ) {
        $this->apiclient = $apiclient;
        $this->environment = $environment;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {

        try {
            $response = $this->apiclient->sendPurchaseEvent($this->getPurchases());

            if (!$response) {
                $this->logger->error("Failed to sync purchase event", [$response]);
            }
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Observer/Purchase->execute() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
        }
    }

    /**
     * Helper function. Sets data to be sent to STACC on purchase event.
     * @return array
     */
    private function getPurchases()
    {
        try {
            $items = [];
            $collection = $this->checkoutSession->getLastRealOrder()->getAllVisibleItems();
            $store = $this->storeManager->getStore();
            foreach ($collection as $product) {
                $formatted_price = $product->getRowTotal() - $product->getDiscountAmount() + $product->getTaxAmount();
                $prod = [
                    'item_id'    => $product->getProductId(),
                    'quantity'   => $product->getQtyOrdered(),
                    'price'      => $product->getPrice(),
                    'properties' => [
                        'formatted_price' => $formatted_price,
                        'sku'             => $product->getSku(),
                        'tax_amount'      => $product->getTaxAmount(),
                        'currency'        => $this->environment->getCurrencyCode(),
                        'current_crcy'    => $store->getCurrentCurrencyCode(),
                        'lang'            => $store->getCode()
                    ]
                ];
                $items[] = $prod;
            }

            return $items;
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Observer/Purchase->getPurchases() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            return [];
        }
    }
}
