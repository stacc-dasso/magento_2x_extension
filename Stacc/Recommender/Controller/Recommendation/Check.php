<?php

namespace Stacc\Recommender\Controller\Recommendation;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\LayoutInterface;
use Stacc\Recommender\Logger\Logger;
use Stacc\Recommender\Network\Environment;

/**
 * Class Check
 * @package Stacc\Recommender\Controller\Recommendation
 */
class Check extends Action
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
     * @var LayoutInterface
     */
    protected $_layout;

    /**
     * Check constructor.
     * @param Environment $environment
     * @param Logger $logger
     * @param LayoutInterface $layout
     * @param Context $context
     */
    public function __construct(Environment $environment, Logger $logger, LayoutInterface $layout, Context $context)
    {
        parent::__construct($context);

        $this->_environment = $environment;
        $this->_logger = $logger;
        $this->_layout = $layout;
        $this->_layout->getUpdate()->addHandle('default');
    }

    /**
     * Receives view events and executes Observer
     */
    public function execute()
    {
        try {
            $urlHash = (string)$this->getRequest()->getParam('h');
            $timestamp = $this->getRequest()->getParam('t');

            if ($this->auth_api($urlHash)) {
                $this->getResponse()->setBody($timestamp);
            } else {
                $this->_logger->error("Failed to authenticate the request");
                $this->getResponse()->setBody("");
            }
        } catch (\Exception $exception) {
            $this->_logger->critical("Controller/Recommendation/Check.php->execute() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            $this->getResponse()->setBody("");
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
            $this->_logger->critical("Controller/Recommendation/Check.php->auth_api() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }
}
