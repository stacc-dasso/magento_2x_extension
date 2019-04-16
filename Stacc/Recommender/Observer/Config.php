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
    protected $apiclient;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Config constructor.
     * @param Apiclient $apiclient
     * @param Logger $logger
     */
    public function __construct(Apiclient $apiclient, Logger $logger)
    {
        $this->logger = $logger;
        $this->apiclient = $apiclient;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            if ($observer->getEvent()) {
                $this->logger->info("Saved Shop ID and API Key");
            }
        } catch (\Exception $exception) {
            $this->logger
                ->error(
                    "Observer/Config->execute() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
        }
    }
}
