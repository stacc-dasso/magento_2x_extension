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
    protected $environment;

    /**
     * @var HttpRequestInterface
     */
    protected $httpRequest;

    /**
     * @var Logger
     */
    protected $cookie;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Apiclient constructor.
     * @param Environment $environment
     * @param HttpRequestInterface $httpRequest
     * @param Cookie $cookie
     * @param Logger $logger
     */
    public function __construct(
        Environment $environment,
        HttpRequestInterface $httpRequest,
        Cookie $cookie,
        Logger $logger
    ) {
        $this->environment = $environment;
        $this->httpRequest = $httpRequest;
        $this->cookie = $cookie;
        $this->logger = $logger;
    }

    /**
     * @return Environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return HttpRequestInterface
     */
    public function getHttpRequest()
    {
        return $this->httpRequest;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
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
            $environment = $this->environment;
            $httpRequest = $this->httpRequest;

            $data = $this->createDataArrayForRecsRequest($productId, $blockId, $environment);

            $url = $environment->getRecommendationsURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($data, $url, $timeout);

            $json_output = json_decode($output);

            if (isset($json_output->items)) {
                return $json_output->items;
            } else {
                return [];
            }
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Apiclient->askRecommendations() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            return [];
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
            $environment = $this->environment;
            $httpRequest = $this->httpRequest;
            $data = $this->createDataArrayForRequest($productId, $environment);

            $url = $environment->getViewEventURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($data, $url, $timeout);

            return $output;
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Apiclient->sendViewEvent() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
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
    public function sendSearchEvent($query, $filters = [])
    {
        try {
            $environment = $this->environment;
            $httpRequest = $this->httpRequest;
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
            $this->logger
                ->critical(
                    "Apiclient->sendSearchEvent() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
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
            $environment = $this->environment;
            $httpRequest = $this->httpRequest;

            $data = $this->createDataArrayForRequest($productId, $environment);

            $url = $environment->getAddToCartEventURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($data, $url, $timeout);

            return $output;
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Apiclient->sendAddToCartEvent() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
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
            $environment = $this->environment;
            $httpRequest = $this->httpRequest;

            $data = $this->createDataArrayForPurchaseRequest($itemsArray, $environment);

            $url = $environment->getPurchaseEventURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($data, $url, $timeout);

            return $output;
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Apiclient->sendPurchaseEvent() Exception: ",
                    [
                        get_class($exception),
                    $exception->getMessage(),
                    $exception->getCode()]
                );
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
            $environment = $this->environment;
            $httpRequest = $this->httpRequest;

            $url = $environment->getCatalogSyncURL();

            $timeout = $environment->getTimeout();

            $output = $httpRequest->postData($bulk, $url, $timeout);

            return $output;
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Apiclient->sendProducts() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
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
            $environment = $this->environment;
            $httpRequest = $this->httpRequest;
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
            $this->logger
                ->critical(
                    "Apiclient->sendPurchaseEvent() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
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
            $this->logger
                ->critical(
                    "Helper/Apiclient->sendCheckCredentials() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
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
            $environment = $this->environment;

            $customerInfo = $customerInfo ?: $environment->identifyCustomer();
            $website = $website ?: $environment->getWebsite();
            $store = $storeCode ?: $environment->getStoreCode();
            $userAgent = $environment->getUserAgent();
            $lang = $environment->getLang();
            $currencyCode = $currency ?: $environment->getCurrencyCode();
            $extVersion = $environment->getVersion();
            $localeCode = $environment->getLocaleCode();
            $cookie = $this->cookie->isUserNotAllowSaveCookie();
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
            $this->logger
                ->critical(
                    "Apiclient->getProperties() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            return [];
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
