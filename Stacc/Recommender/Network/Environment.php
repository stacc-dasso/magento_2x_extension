<?php

namespace Stacc\Recommender\Network;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Visitor;
use Magento\Framework\Locale\Resolver;
use Stacc\Recommender\Logger\Logger;
use Magento\Framework\HTTP\Header;

/**
 * Class Environment
 * @package Stacc\Recommender\Network
 */
class Environment
{
    /**
     * USER_AGENT_CODE
     */
    const CLI_USER_AGENT = 'cli_executed_stacc_recommender_extension';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Visitor
     */
    protected $customerVisitor;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Resolver
     */
    protected $resolver;

    /**
     * @var Header
     */
    private $header;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Path to STACC API
     *
     * @var array
     */
    private $baseApiUrl = "https://recommender.stacc.cloud/";

    /**
     * List of api paths
     *
     * @var array
     */
    private $apiPaths = [
        "main_api" => "api/v3",
        "m2x_api" => "api/magento/2x"
    ];

    /**
     * Array of endpoints for extension
     *
     * @var array
     */
    private $endpoints = [
        'add_to_cart' => '/send_add_to_cart',
        'catalog_sync' => '/catalog_sync',
        'get_recs' => '/get_recs',
        'purchase' => '/send_purchase',
        'view' => '/send_view',
        'log' => '/send_log',
        'search' => '/send_search',
        'check' => '/check_credentials'
    ];

    /**
     * Timeout for extension events
     *
     * @var int
     */
    private $timeout = 3000;

    /**
     * Version number of extension
     * @var
     */
    protected $version = '4.0.0';

    /**
     * Environment constructor.
     * @param StoreManagerInterface $storeManager
     * @param Visitor $customerVisitor
     * @param ScopeConfigInterface $scopeConfig
     * @param Resolver $resolver
     * @param Header $header
     * @param Logger $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Visitor $customerVisitor,
        ScopeConfigInterface $scopeConfig,
        Resolver $resolver,
        Header $header,
        Logger $logger
    ) {

        $this->storeManager = $storeManager;
        $this->customerVisitor = $customerVisitor;
        $this->scopeConfig = $scopeConfig;
        $this->resolver = $resolver;
        $this->header = $header;
        $this->logger = $logger;
    }

    /**
     * Returns the corresponding endpoint for the value
     *
     * @param $value
     * @return mixed
     */
    public function getEndpoint($value)
    {
        return $this->endpoints[$value];
    }

    /**
     * Get customer related data
     *
     * @return array
     */
    public function identifyCustomer()
    {
        try {
            $customer_visitor = $this->customerVisitor;
            $session_id = $customer_visitor->getData('session_id');
            $visitor_id = $customer_visitor->getData('visitor_id');
            $customer_id = $customer_visitor->getData('customer_id');

            $customer_id = $customer_id ? $customer_id : "";
            $visitor_id = $visitor_id ? $visitor_id : "";
            $session_id = $session_id ? $session_id : "";
            $customer_info = [
                'session_id' => $session_id,
                'visitor_id' => $visitor_id,
                'customer_id' => $customer_id,
            ];
            return $customer_info;
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->identifyCustomer() Exception: ",
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
     * Returns Currency Code
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        try {
            return $this->getStore()->getCurrentCurrency()->getCode();
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->identifyCustomer() Exception: ",
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
     * Returns STACC API URL
     *
     * @return string
     */
    public function getApiUrl()
    {
        try {
            return $this->baseApiUrl . $this->apiPaths["main_api"];
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getApiUrl() Exception: ",
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
     * Returns STACC M1 URL
     *
     * @return string
     */
    public function getM2Url()
    {
        try {
            return $this->baseApiUrl . $this->apiPaths["m2x_api"];
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getApiUrl() Exception: ",
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
     * Return endpoint URL for getting Recommendations
     *
     * @return string
     */
    public function getRecommendationsURL()
    {
        try {
            return $this->getApiUrl() . $this->getEndpoint('get_recs');
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getRecommendationsURL() Exception: ",
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
     * Return endpoint URL for the View event
     *
     * @return string
     */
    public function getViewEventURL()
    {
        try {
            return $this->getApiUrl() . $this->getEndpoint('view');
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getViewEventURL() Exception: ",
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
     * Return endpoint URL for the Search event
     *
     * @return string
     */
    public function getSearchEventURL()
    {
        try {
            return $this->getApiUrl() . $this->getEndpoint('search');
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getSearchEventURL() Exception: ",
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
     * Return endpoint URL for the Add to Cart event
     *
     * @return string
     */
    public function getAddToCartEventURL()
    {
        try {
            return $this->getApiUrl() . $this->getEndpoint('add_to_cart');
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getAddToCartEventURL() Exception: ",
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
     * Return endpoint URL for the Purchase event
     *
     * @return string
     */
    public function getPurchaseEventURL()
    {
        try {
            return $this->getApiUrl() . $this->getEndpoint('purchase');
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getPurchaseEventURL() Exception: ",
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
     * Return endpoint URL for the Catalog Syncing event
     *
     * @return string
     */
    public function getCatalogSyncURL()
    {
        try {
            return $this->getApiUrl() . $this->getEndpoint('catalog_sync');
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getCatalogSyncURL() Exception: ",
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
     * Returns endpoint URL for logs
     *
     * @return string
     */
    public function getLogsURL()
    {
        try {
            return $this->getApiUrl() . $this->getEndpoint('log');
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getLogsURL() Exception: ",
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
     * Returns endpoint URL for checking credentials
     *
     * @return string
     */
    public function getCheckCredentialsURL()
    {
        try {
            return $this->getM2Url() . $this->getEndpoint('check');
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Helper/Environment->getCheckCredentialsURL(() Exception: ",
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
     * Returns STACC Client ID that is set in admin panel
     *
     * @return mixed
     */
    public function getShopId()
    {
        try {
            return $this->scopeConfig
                ->getValue(
                    'stacc_recommender/configuration/stacc_shop_id',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getShopId() Exception: ",
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
     * Returns STACC API Key that is set in admin panel
     *
     * @return mixed
     */
    public function getApiKey()
    {
        try {
            return $this->scopeConfig
                ->getValue(
                    'stacc_recommender/configuration/stacc_api_key',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getApiKey() Exception: ",
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
     * @return array
     */
    public function getCredentials()
    {
        try {
            return [
                "id" => $this->getShopId(),
                "key" => $this->getApiKey()
            ];
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getCredentials() Exception: ",
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
     * Returns extension version
     *
     * @return string
     */
    public function getVersion()
    {
        try {
            return (string)$this->version;
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getVersion() Exception: ",
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
     * Return store language code
     *
     * @return string
     */
    public function getLang()
    {
        try {
            return $this->getStore()->getCode();
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getLang() Exception: ",
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
     * Returns website name
     *
     * @return string
     */
    public function getWebsite()
    {
        try {
            return $this->storeManager->getWebsite()->getName();
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Helper/Environment->getWebsite() Exception: ",
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
     * Returns Magentos store object
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        try {
            return $this->storeManager->getStore();
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getStore() Exception: ",
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
     * Returns Magentos store objects code
     *
     * @return string
     */
    public function getStoreCode()
    {
        try {
            return $this->getStore()->getCode();
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getStore() Exception: ",
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
     * Returns Language code
     *
     * @return null|string
     */
    public function getLocaleCode()
    {
        try {
            return $this->resolver->getLocale();
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getLocaleCode() Exception: ",
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
     * Returns timeout
     *
     * @return int
     */
    public function getTimeout()
    {
        try {
            return $this->timeout;
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getLocaleCode() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            return 3000;
        }
    }

    /**
     * Return user agent
     *
     * @return string
     */
    public function getUserAgent()
    {
        try {
            return $this->header->getHttpUserAgent() ?: self::CLI_USER_AGENT;
        } catch (Exception $exception) {
            $this->logger
                ->critical(
                    "Environment->getLocaleCode() Exception: ",
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
     * Return timestamp
     *
     * @return int
     */
    public function getTime()
    {
        return microtime(true);
    }
}
