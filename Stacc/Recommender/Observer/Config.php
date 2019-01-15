<?php

namespace Stacc\Recommender\Observer;

use Stacc\Recommender\Network\Apiclient;
use Stacc\Recommender\Logger\Logger;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 * Class Config
 * @package Stacc\Recommender\Observer
 */
class Config implements ObserverInterface
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
     * Config constructor.
     * @param Apiclient $apiclient
     * @param Logger $logger
     */
    public function __construct(Apiclient $apiclient, Logger $logger)
    {
        $this->_logger = $logger;
        $this->_apiClient = $apiclient;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            if ($observer->getEvent()) {
                $this->_logger->info("Saved Shop ID and API Key");
            }
        } catch (\Exception $exception) {
            $this->_logger->error("Observer/Config->execute() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }
    }
}