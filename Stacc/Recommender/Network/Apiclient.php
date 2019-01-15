<?php


namespace Stacc\Recommender\Network;

use Exception;
use Magento\Cookie\Helper\Cookie;
use Stacc\Recommender\Logger\Logger;

/**
 * Class Apiclient
 * @package Stacc\Recommender\Network
 */
class Apiclient
{
    /**
     * @var Environment
     */
    protected $_environment;

    /**
     * @var HttpRequest
     */
    protected $_httpRequest;

    /**
     * @var Logger
     */
    protected $_cookie;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * Apiclient constructor.
     * @param Environment $environment
     * @param HttpRequest $httpRequest
     * @param Cookie $cookie
     * @param Logger $logger
     */
    public function __construct(
        Environment $environment,
        HttpRequest $httpRequest,
        Cookie $cookie,
        Logger $logger
    )
    {
        $this->_environment = $environment;
        $this->_httpRequest = $httpRequest;
        $this->_cookie = $cookie;
        $this->_logger = $logger;
    }

    /**
     * @return Environment
     */
    public function getEnvironment()
    {
        return $this->_environment;
    }

    /**
     * @return HttpRequest
     */
    public function getHttpRequest()
    {
        return $this->_httpRequest;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Retrieve recommendations from STACC Recommender API
     *
     * @param $productId
     * @param $blockId
     * @return array
     */
    public function askRecommendations($productId, $blockId)
    {
        try {
            $environment = $this->_environment;
            $httpRequest = $this->_httpRequest;

            $data = $this->createDataArrayForRecsRequest($productId, $blockId, $environment);

            $url = $environment->getRecommendationsURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($data, $url, $timeout);

            $json_output = json_decode($output);

            if (isset($json_output->items)) {
                return $json_output->items;
            } else {
                return array();
            }

        } catch (Exception $exception) {
            $this->_logger->critical("Apiclient->askRecommendations() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return array();
        }

    }

    /**
     * Send View Event To STACC Recommender API
     *
     * @param $productId
     * @return mixed
     */
    public function sendViewEvent($productId)
    {
        try {
            $environment = $this->_environment;
            $httpRequest = $this->_httpRequest;
            $data = $this->createDataArrayForRequest($productId, $environment);

            $url = $environment->getViewEventURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($data, $url, $timeout);

            return $output;
        } catch (Exception $exception) {
            $this->_logger->critical("Apiclient->sendViewEvent() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "{}";
        }
    }

    /**
     * Send Search Event To STACC Recommender API
     *
     * @param $query
     * @param array $filters
     * @return mixed
     */
    public function sendSearchEvent($query, $filters = array())
    {
        try {
            $environment = $this->_environment;
            $httpRequest = $this->_httpRequest;
            $customerInfo = $environment->identifyCustomer();
            $website = $environment->getWebsite();
            $storeCode = $environment->getStoreCode();
            $data = [
                "stacc_id" => (string)$customerInfo['visitor_id'],
                "query" => (string)$query,
                "filters" => $filters,
                "website" => $website,
                'store' => $storeCode,
                "properties" => $this->getProperties($customerInfo, $website, $storeCode)
            ];

            $url = $environment->getSearchEventURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($data, $url, $timeout);
            return $output;
        } catch (Exception $exception) {
            $this->_logger->critical("Apiclient->sendSearchEvent() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "{}";
        }
    }

    /**
     * Send Add To Cart Event To STACC Recommender API
     *
     * @param $productId
     * @return mixed
     */
    public function sendAddToCartEvent($productId)
    {
        try {
            $environment = $this->_environment;
            $httpRequest = $this->_httpRequest;

            $data = $this->createDataArrayForRequest($productId, $environment);

            $url = $environment->getAddToCartEventURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($data, $url, $timeout);

            return $output;
        } catch (Exception $exception) {
            $this->_logger->critical("Apiclient->sendAddToCartEvent() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "{}";
        }
    }

    /**
     * Send Purchase Event To STACC Recommender API
     *
     * @param $itemsArray
     * @return mixed
     */
    public function sendPurchaseEvent($itemsArray)
    {
        try {
            $environment = $this->_environment;
            $httpRequest = $this->_httpRequest;

            $data = $this->createDataArrayForPurchaseRequest($itemsArray, $environment);

            $url = $environment->getPurchaseEventURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($data, $url, $timeout);

            return $output;
        } catch (Exception $exception) {
            $this->_logger->critical("Apiclient->sendPurchaseEvent() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "{}";
        }
    }

    /**
     * Sync products to STACC API
     *
     * @param $bulk
     * @return mixed
     */
    public function sendProducts($bulk)
    {
        try {

            $environment = $this->_environment;
            $httpRequest = $this->_httpRequest;

            $url = $environment->getCatalogSyncURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($bulk, $url, $timeout);

            return $output;
        } catch (Exception $exception) {
            $this->_logger->critical("Apiclient->sendProducts() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "{data: false, error: true}";
        }
    }

    /**
     * Send logs to STACC API
     *
     * @param $logs
     * @return mixed
     */
    public function sendLogs($logs)
    {
        try {
            $environment = $this->_environment;
            $httpRequest = $this->_httpRequest;
            $data = [
                'logs' => $logs,
                'properties' => [
                    'user_agent' => $environment->getUserAgent(),
                ]
            ];

            $url = $environment->getLogsURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($data, $url, $timeout);

            return $output;
        } catch (Exception $exception) {
            $this->_logger->critical("Apiclient->sendPurchaseEvent() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return "{}";
        }
    }

    /**
     * Send request to STACC API
     *
     * @param $data
     * @return string
     */
    public function sendCheckCredentials($data)
    {
        try {
            $environment = $this->getEnvironment();
            $httpRequest = $this->getHttpRequest();

            $url = $environment->getCheckCredentialsURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($data, $url, $timeout);

            return $output;
        } catch (Exception $exception) {
            $this->_logger->critical("Helper/Apiclient->sendCheckCredentials() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return false;
        }
    }


    /**
     * Returns properties about website and extension for the events
     *
     * @param mixed $customerInfo
     * @param mixed $website
     * @param mixed $storeCode
     * @param mixed $currency
     * @return array
     */
    private function getProperties($customerInfo = null, $website = null, $storeCode = null, $currency = null)
    {

        try {
            $environment = $this->_environment;

            $customerInfo = $customerInfo ?: $environment->identifyCustomer();
            $website = $website ?: $environment->getWebsite();
            $store = $storeCode ?: $environment->getStoreCode();
            $userAgent = $environment->getUserAgent();
            $lang = $environment->getLang();
            $currencyCode = $currency ?: $environment->getCurrencyCode();
            $extVersion = $environment->getVersion();
            $localeCode = $environment->getLocaleCode();
            $cookie = $this->_cookie->isUserNotAllowSaveCookie();
            return array_merge(
                $customerInfo,
                [
                    'website' => $website,
                    'user_agent' => $userAgent,
                    'store' => $store,
                    'lang' => $lang,
                    'lang_code' => $localeCode,
                    'currency' => $currencyCode,
                    'extension_version' => $extVersion,
                    'cookie_allowed' => $cookie
                ]
            );
        } catch (Exception $exception) {
            $this->_logger->critical("Apiclient->getProperties() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return array();
        }
    }

    /**
     * @param $productId
     * @param $blockId
     * @param Environment $environment
     * @return array
     */
    private function createDataArrayForRecsRequest($productId, $blockId, Environment $environment)
    {
        $customerInfo = $environment->identifyCustomer();
        $website = $environment->getWebsite();
        $storeCode = $environment->getStoreCode();

        //  Get recommendations from the API
        $data = [
            'stacc_id' => (string)$customerInfo['visitor_id'],
            'item_id' => (string)$productId,
            'block_id' => (string)$blockId,
            'website' => $website,
            'store' => $storeCode,
            'properties' => $this->getProperties($customerInfo, $website, $storeCode)
        ];
        return $data;
    }

    /**
     * @param $productId
     * @param Environment $environment
     * @return array
     */
    private function createDataArrayForRequest($productId, Environment $environment)
    {
        $customerInfo = $environment->identifyCustomer();
        $website = $environment->getWebsite();
        $storeCode = $environment->getStoreCode();
        $data = [
            'stacc_id' => (string)$customerInfo['visitor_id'],
            'item_id' => (string)$productId,
            'website' => $website,
            'store' => $storeCode,
            'properties' => $this->getProperties($customerInfo, $website, $storeCode)
        ];
        return $data;
    }

    /**
     * @param $itemsArray
     * @param Environment $environment
     * @return array
     */
    private function createDataArrayForPurchaseRequest($itemsArray, Environment $environment)
    {
        $customerInfo = $environment->identifyCustomer();
        $website = $environment->getWebsite();
        $storeCode = $environment->getStoreCode();
        $currencyCode = $environment->getCurrencyCode();
        $data = [
            'stacc_id' => (string)$customerInfo['visitor_id'],
            'item_list' => $itemsArray,
            'currency' => $currencyCode,
            'website' => $website,
            'store' => $storeCode,
            'properties' => $this->getProperties($customerInfo, $website, $storeCode, $currencyCode),
        ];
        return $data;
    }
}
