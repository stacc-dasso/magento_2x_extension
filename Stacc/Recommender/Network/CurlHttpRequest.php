<?php

namespace Stacc\Recommender\Network;


use Stacc\Recommender\Logger\Logger;
use \Magento\Framework\HTTP\Client\Curl;

/**
 * Class CurlHttpRequest
 * @package Stacc\Recommender\Network
 */
class CurlHttpRequest implements HttpRequest
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
     * @var Curl
     */
    protected $_curl;

    /**
     * CurlHttpRequest constructor.
     * @param Environment $environment
     * @param Logger $logger
     * @param Curl $curl
     */
    public function __construct(Environment $environment, Curl $curl, Logger $logger)
    {
        $this->_environment = $environment;
        $this->_curl = $curl;
        $this->_logger = $logger;
    }


    /**
     * Method to send data
     *
     * @param $data
     * @param $url
     * @param int $timeout
     * @return JSON|string
     */
    public function postData($data, $url, $timeout = 3000)
    {
        try {
            $credentials = $this->_environment->getCredentials();

            // Init request
            $this->_curl->setOptions(
                [
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_USERPWD => $credentials['id'] . ":" . $credentials['key'],
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_FRESH_CONNECT => 1,
                    CURLOPT_TIMEOUT_MS => $timeout
                ]
            );

            $this->_curl->post($url, json_encode($data));
            // Send request
            $output = $this->_curl->getBody();
            $httpcode = $this->_curl->getStatus();

            if ($httpcode != 200) {
                $this->_logger->error("Received error from HTTP request", ['error' => strval($httpcode)]);
            }

            return $output;
        } catch (\Exception $exception) {
            $this->_logger->critical("Network/Httprequest->postData() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
            return '{"error": "Failed to start a connection!"}';
        }

    }

}