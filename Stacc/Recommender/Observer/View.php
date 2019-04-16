<?php

namespace Stacc\Recommender\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Stacc\Recommender\Logger\Logger;
use Stacc\Recommender\Network\Apiclient;

/**
 * Class View
 * @package Stacc\Recommender\Observer
 */
class View implements ObserverInterface
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
     * View constructor.
     * @param Apiclient $apiclient
     * @param Logger $logger
     */
    public function __construct(Apiclient $apiclient, Logger $logger)
    {
        $this->apiclient = $apiclient;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            if ($observer->getEvent()->getProduct()) {
                $productId = $observer->getEvent()->getProduct()->getId();
                $this->apiclient->sendViewEvent($productId);
            }
        } catch (\Exception $exception) {
            $this->logger
                ->error(
                    "Observer/View->execute() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
        }
    }
}
