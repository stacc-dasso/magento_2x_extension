<?php

namespace Stacc\Recommender\Block\Adminhtml\Menu\Field;

use Stacc\Recommender\Network\Environment;
use Stacc\Recommender\Logger\Logger;
use Stacc\Recommender\Network\Apiclient;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Phrase;
use Magento\Backend\Block\Template\Context;

/**
 * Class Version
 * @package Stacc\Recommender\Block\Adminhtml\Menu\Field
 */
class Version extends Field
{
    /**
     * @var Environment
     */
    protected $_environment;
    /**
     * @var Apiclient
     */
    protected $_apiclient;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var
     */
    protected $_element;

    /**
     * Version constructor.
     * @param Environment $environment
     * @param Apiclient $apiclient
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Environment $environment,
        Apiclient $apiclient,
        Logger $logger,
        StoreManagerInterface $storeManager,
        Context $context, array $data = []
    )
    {
        parent::__construct($context, $data);

        $this->_environment = $environment;
        $this->_apiclient = $apiclient;
        $this->_logger = $logger;
        $this->_storeManager = $storeManager;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        try {
            if ($element) {
                $this->_element = $element;
            }
            $error = "";
            $apiKey = $this->_environment->getApiKey();
            $shopID = $this->_environment->getShopId();

            if ($apiKey && $shopID) {
                $data = $this->buildData();
                $request = $this->_apiclient->sendCheckCredentials($data);
                $error = $this->requestResponse($request);
            }

            return (string)$this->_environment->getVersion() . $error;
        } catch (\Exception $exception) {
            $this->_logger->critical("Block/Adminhtml/Menu/Field/Version->_getElementHtml() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
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
                return "<br/><span style='color:red'>" . new Phrase("Please check your API Key and Shop ID") . "</span>";
            }
            if (!$request) {
                return "<br/><span style='color:red'>" . new Phrase("Can't connect to STACC server") . "</span>";
            }
            return "<br/><span style='color:red'>" . new Phrase("Failed to verify the Shop ID and the API Key") . "</span>";
        } else if($request == '{}'){
            return "<br/><span style='color:green'>" . new Phrase("Shop ID and API Key verified") . "</span>";
        } else{
            return "<br/><span style='color:red'>" . new Phrase("Can't connect to STACC server") . "</span>";
        }
    }

    /**
     * @return array
     */
    protected function buildData()
    {
        try {
            $defaultStoreId = $this->_storeManager->getDefaultStoreView()->getId();

            $defaultUrl = $this->_storeManager->getStore($defaultStoreId)->getUrl();

            $data = [
                "media_url" => $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . "catalog/product/",
                "js" => $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_STATIC),
                "skins" => $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_STATIC),
                "base_url" => $this->_storeManager->getStore()->getBaseUrl(),
                "direct_link" => $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_DIRECT_LINK),
                "default_store" => $defaultUrl,
            ];
            return $data;
        } catch (\Exception $exception) {
            $this->_logger->critical("Block/Adminhtml/Menu/Field/Version->buildData() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return [];
        }
    }
}

