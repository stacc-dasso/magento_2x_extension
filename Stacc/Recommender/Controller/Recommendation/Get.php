<?php

namespace Stacc\Recommender\Controller\Recommendation;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\LayoutInterface;
use Stacc\Recommender\Logger\Logger;

/**
 * Class Get
 * @package Stacc\Recommender\Controller\Recommendation
 */
class Get extends Action
{

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * Get constructor.
     * @param Logger $logger
     * @param LayoutInterface $layout
     * @param Context $context
     */
    public function __construct(Logger $logger, LayoutInterface $layout, Context $context)
    {
        parent::__construct($context);

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
            $productId = $this->getRequest()->getParam('productId');
            $blockId = $this->getRequest()->getParam('blockId');
            $template = $this->getRequest()->getParam('template');
            if (!isset($productId)) {
                return "";
            }

            $block = $this->layout
                ->createBlock(
                    'Stacc\Recommender\Block\Recommendation',
                    $blockId
                )
                ->setTimestamp(microtime(true))
                ->setProductId($productId)
                ->setBlockId($blockId)
                ->setTemplate($template);

            $this->getResponse()->setBody($block->toHtml())
                ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Controller/Recommendation/Get.php->execute() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            $this->getResponse()->setBody("");
            return null;
        }
    }
}
