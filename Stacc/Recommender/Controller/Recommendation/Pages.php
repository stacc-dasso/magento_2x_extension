<?php

namespace Stacc\Recommender\Controller\Recommendation;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Stacc\Recommender\Logger\Logger;
use Stacc\Recommender\Network\Environment;
use Stacc\Recommender\Model\SyncFactory;

class Pages extends Action
{
    /**
     * For store id verification to run on entered id
     */
    const TYPE_STORE = "store";

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var SyncFactory
     */
    protected $syncFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * Sync constructor.
     * @param Environment $environment
     * @param StoreManagerInterface $storeManager
     * @param SyncFactory $syncFactory
     * @param Logger $logger
     * @param LayoutInterface $layout
     * @param Context $context
     */
    public function __construct(
        Environment $environment,
        StoreManagerInterface $storeManager,
        SyncFactory $syncFactory,
        Logger $logger,
        LayoutInterface $layout,
        Context $context
    ) {
        parent::__construct($context);

        $this->environment = $environment;
        $this->storeManager = $storeManager;
        $this->syncFactory = $syncFactory;
        $this->logger = $logger;
        $this->layout = $layout;
        $this->layout->getUpdate()->addHandle('default');
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
            if ($this->authApi($urlHash)) {
                $sync = $this->syncFactory->create();

                $pagesArr = $sync->getAmountOfPages($storeId);
                $this->getResponse()->setBody(json_encode(array_merge($pagesArr, ['timestamp' => $timestamp])));
            } else {
                $this->logger->error("Failed to authenticate the request");
                $this->getResponse()->setBody("");
            }
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "controllers/RecommendationController->pagesAction() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            return null;
        }
    }
    /**
     * Method that checks the hash of url
     *
     * @param $hash
     * @return bool
     */
    private function authApi($hash)
    {
        try {
            $mainHash = hash("sha256", $this->environment->getShopId() . $this->environment->getApiKey());
            return $mainHash == $hash;
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Controller/Recommendation/Product.php->auth_api() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
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
                    $store = $this->storeManager->getStore($id);
                    ;
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
            $this->logger
                ->critical(
                    "Controller/Recommendation/Sync.php->verify_id() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            return null;
        }
    }
}
