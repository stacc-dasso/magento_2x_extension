<?php
namespace Stacc\Recommender\Observer;

use Stacc\Recommender\Network\Apiclient;
use Stacc\Recommender\Logger\Logger;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Search\Model\QueryFactory;

/**
 * Class Search
 * @package Stacc\Recommender\Observer
 */
class Search implements ObserverInterface{
    /**
     * @var Apiclient
     */
    protected $_apiClient;

    /**
     * @var QueryFactory
     */
    protected $_queryFactory;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * Search constructor.
     * @param Apiclient $apiclient
     * @param QueryFactory $queryFactory
     * @param Logger $logger
     */
    public function __construct(Apiclient $apiclient, QueryFactory $queryFactory, Logger $logger)
    {
        $this->_apiClient = $apiclient;
        $this->_queryFactory = $queryFactory;
        $this->_logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            if ($observer->getEvent()) {
                $query = $this->_queryFactory->get()->getQueryText();
                $this->_apiClient->sendSearchEvent($query);
            }
        } catch (\Exception $exception) {
            $this->_logger->error("Observer/Search->execute() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }
    }
}