<?php

namespace Stacc\Recommender\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Stacc\Recommender\Logger\Logger;
use Stacc\Recommender\Network\Apiclient;

/**
 * Class Cart
 * @package Stacc\Recommender\Observer
 */
class Cart implements ObserverInterface
{
    /**
     * @var Apiclient
     */
    protected $apiClient;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * AddToCart constructor.
     *
     * @param Apiclient $apiClient
     * @param Logger $logger
     */
    public function __construct(Apiclient $apiClient, Logger $logger)
    {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            $response = $this->apiClient->sendAddToCartEvent($observer->getEvent()->getProduct()->getId());

            if (!$response) {
                $this->logger->error("Failed to sync purchase event", [$response]);
            }
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Observer/Cart->execute() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
        }
    }
}
