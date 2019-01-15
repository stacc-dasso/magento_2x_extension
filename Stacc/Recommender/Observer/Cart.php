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
    protected $_apiClient;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * AddToCart constructor.
     *
     * @param Apiclient $apiClient
     * @param Logger $logger
     */
    public function __construct(Apiclient $apiClient, Logger $logger)
    {
        $this->_apiClient = $apiClient;
        $this->_logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            $response = $this->_apiClient->sendAddToCartEvent($observer->getEvent()->getProduct()->getId());

            if (!$response) {
                $this->_logger->error("Failed to sync purchase event", array($response));
            }

        } catch (\Exception $exception) {
            $this->_logger->critical("Observer/Cart->execute() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }
    }
}