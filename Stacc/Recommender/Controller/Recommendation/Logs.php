<?php

namespace Stacc\Recommender\Controller\Recommendation;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\LayoutInterface;
use Stacc\Recommender\Logger\Logger;
use Stacc\Recommender\Network\Environment;
use Stacc\Recommender\Model\LogdispatcherFactory;

/**
 * Class Logs
 * @package Stacc\Recommender\Controller\Recommendation
 */
class Logs extends Action
{
    /**
     * @var Environment
     */
    protected $_environment;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var LogdispatcherFactory
     */
    protected $_logdispatcherFactory;

    /**
     * @var
     */
    protected $_syncFactory;

    /**
     * @var LayoutInterface
     */
    protected $_layout;

    /**
     * Logs constructor.
     * @param Environment $environment
     * @param Logger $logger
     * @param LogdispatcherFactory $logdispatcherFactory
     * @param LayoutInterface $layout
     * @param Context $context
     */
    public function __construct(Environment $environment, Logger $logger, LogdispatcherFactory $logdispatcherFactory, LayoutInterface $layout, Context $context)
    {
        parent::__construct($context);

        $this->_environment = $environment;
        $this->_logger = $logger;
        $this->_logdispatcherFactory = $logdispatcherFactory;
        $this->_layout = $layout;
        $this->_layout->getUpdate()->addHandle('default');

    }

    /**
     * Receives view events and executes Observer
     */
    public function execute()
    {
        try {
            $url_hash = (string)$this->getRequest()->getParam('h');
            $timestamp = $this->getRequest()->getParam('t');

            if ($this->auth_api($url_hash)) {
                $logger = $this->_logdispatcherFactory->create();
                $logs = $logger->sendLogs();
                $this->getResponse()->setBody($timestamp . " ". $logs->getSentAmount());
            } else {
                $this->_logger->error("Failed to authenticate the request");
                $this->getResponse()->setBody("");
            }
        } catch (\Exception $exception) {
            $this->_logger->critical("controllers/RecommendationController->execute() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }

    /**
     * Method that checks the hash of url
     *
     * @param $hash
     * @return bool
     */
    private function auth_api($hash)
    {
        try {
            $mainHash = hash("sha256", $this->_environment->getShopId() . $this->_environment->getApiKey());

            return $mainHash == $hash;
        } catch (\Exception $exception) {
            $this->_logger->critical("Controller/Recommendation/Product.php->auth_api() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }

}

