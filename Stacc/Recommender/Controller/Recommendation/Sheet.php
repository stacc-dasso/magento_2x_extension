<?php

namespace Stacc\Recommender\Controller\Recommendation;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Stacc\Recommender\Logger\Logger;
use Stacc\Recommender\Network\Environment;
use Stacc\Recommender\Model\SyncFactory;

class Sheet extends Action
{
    /**
     * For store id verification to run on entered id
     */
    const TYPE_STORE = "store";

    /**
     * @var Environment
     */
    protected $_environment;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var SyncFactory
     */
    protected $_syncFactory;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var LayoutInterface
     */
    protected $_layout;

    /**
     * Sync constructor.
     * @param Environment $environment
     * @param StoreManagerInterface $storeManager
     * @param SyncFactory $syncFactory
     * @param Logger $logger
     * @param LayoutInterface $layout
     * @param Context $context
     */
    public function __construct(Environment $environment, StoreManagerInterface $storeManager, SyncFactory $syncFactory, Logger $logger, LayoutInterface $layout, Context $context)
    {
        parent::__construct($context);

        $this->_environment = $environment;
        $this->_storeManager = $storeManager;
        $this->_syncFactory = $syncFactory;
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
            set_time_limit(300);
            $urlHash = (string)$this->getRequest()->getParam('h');
            $timestamp = $this->getRequest()->getParam('t');

            $storeId = $this->verifyId($this->getRequest()->getParam('s'), $this::TYPE_STORE);
            $page = $this->getRequest()->getParam('pg');
            if ($this->auth_api($urlHash)) {

                $sync = $this->_syncFactory->create();

                $syncPage = $sync->syncAPage($page, $storeId);

                if ($syncPage) {
                    $this->getResponse()->setBody(json_encode(array_merge($syncPage, ['timestamp' => $timestamp])));
                } else {
                    $this->getResponse()->setBody($timestamp . " " . json_encode($sync->getResponse()));
                }
            } else {
                $this->_logger->error("Failed to authenticate the request");
                $this->getResponse()->setBody("");
            }
        } catch (\Exception $exception) {
            $this->_logger->critical("Controller/Recommendation/Sheet.php->execute() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
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
            $this->_logger->critical("Controller/Recommendation/Sheet.php->auth_api() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }

    /**
     * @param $id
     * @param string $type
     * @return null
     *
     * Verify store Id
     */
    private function verifyId($id, $type = "")
    {
        try {
            if (isset($id)) {
                if ($type == $this::TYPE_STORE) {
                    $store = $this->_storeManager->getStore($id);;
                    if ((int)$id && $store->getId()) {
                        return $id;
                    }
                } else {
                    if ((int)$id) {
                        return $id;
                    }
                    return null;
                }
            }
            return null;
        } catch (\Exception $exception) {
            $this->_logger->critical("Controller/Recommendation/Sheet.php->verify_id() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return null;
        }
    }
}