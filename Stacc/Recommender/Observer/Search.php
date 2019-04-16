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
class Search implements ObserverInterface
{
    /**
     * @var Apiclient
     */
    protected $apiclient;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Search constructor.
     * @param Apiclient $apiclient
     * @param QueryFactory $queryFactory
     * @param Logger $logger
     */
    public function __construct(Apiclient $apiclient, QueryFactory $queryFactory, Logger $logger)
    {
        $this->apiclient = $apiclient;
        $this->queryFactory = $queryFactory;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            if ($observer->getEvent()) {
                $query = $this->queryFactory->get()->getQueryText();
                $this->apiclient->sendSearchEvent($query);
            }
        } catch (\Exception $exception) {
            $this->logger
                ->error(
                    "Observer/Search->execute() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
        }
    }
}
