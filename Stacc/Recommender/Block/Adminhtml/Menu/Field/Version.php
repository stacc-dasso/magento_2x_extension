<?php

namespace Stacc\Recommender\Block\Adminhtml\Menu\Field;

use Stacc\Recommender\Network\Environment;
use Stacc\Recommender\Logger\Logger;
use Stacc\Recommender\Network\Apiclient;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Phrase;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\UrlInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Version
 * @package Stacc\Recommender\Block\Adminhtml\Menu\Field
 */
class Version extends Field
{
    /**
     * @var Environment
     */
    protected $environment;
    /**
     * @var Apiclient
     */
    protected $apiclient;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var
     */
    protected $storeManager;

    /**
     * @var
     */
    protected $element;

    /**
     * Version constructor.
     * @param Environment $environment
     * @param Apiclient $apiclient
     * @param Logger $logger
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Environment $environment,
        Apiclient $apiclient,
        Logger $logger,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->environment = $environment;
        $this->apiclient = $apiclient;
        $this->logger = $logger;
        $this->storeManager = $context->getStoreManager();
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        try {
            if ($element) {
                $this->element = $element;
            }
            $error = "";
            $apiKey = $this->environment->getApiKey();
            $shopID = $this->environment->getShopId();

            if ($apiKey && $shopID) {
                $data = $this->buildData();
                $request = $this->apiclient->sendCheckCredentials($data);
                $error = $this->requestResponse($request);
            }

            return (string)$this->environment->getVersion() . $error;
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Block/Adminhtml/Menu/Field/Version->_getElementHtml() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            return "";
        }
    }

    /**
     * Returns message to display depending on request result
     *
     * @param $request
     * @return string
     */
    private function requestResponse($request)
    {
        if ($request != '{}') {
            if ($request && !json_decode($request)) {
                return "<br/><span style='color:red'>" .
                    new Phrase("Please check your API Key and Shop ID") .
                    "</span>";
            }
            if (!$request) {
                return "<br/><span style='color:red'>" .
                    new Phrase("Can't connect to STACC server") .
                    "</span>";
            }
            return "<br/><span style='color:red'>" .
                new Phrase("Failed to verify the Shop ID and the API Key") .
                "</span>";
        } elseif ($request == '{}') {
            return "<br/><span style='color:green'>" .
                new Phrase("Shop ID and API Key verified") .
                "</span>";
        } else {
            return "<br/><span style='color:red'>" . new Phrase("Can't connect to STACC server") . "</span>";
        }
    }

    /**
     * @return array
     */
    protected function buildData()
    {
        try {
            $defaultStoreId = $this->storeManager->getDefaultStoreView()->getId();

            $defaultUrl = $this->storeManager->getStore($defaultStoreId)->getUrl();

            $data = [
                "media_url" => $this->storeManager
                        ->getStore()
                        ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . "catalog/product/",
                "js" => $this->storeManager
                    ->getStore()
                    ->getBaseUrl(UrlInterface::URL_TYPE_STATIC),
                "skins" => $this->storeManager
                    ->getStore()
                    ->getBaseUrl(UrlInterface::URL_TYPE_STATIC),
                "base_url" => $this->storeManager->getStore()->getBaseUrl(),
                "direct_link" => $this->storeManager
                    ->getStore()
                    ->getBaseUrl(UrlInterface::URL_TYPE_DIRECT_LINK),
                "default_store" => $defaultUrl,
            ];
            return $data;
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Block/Adminhtml/Menu/Field/Version->buildData() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            return [];
        }
    }
}
