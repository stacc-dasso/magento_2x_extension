<?php

namespace Stacc\Recommender\Network;

use Stacc\Recommender\Logger\Logger;
use \Magento\Framework\HTTP\Client\Curl;

/**
 * Class CurlHttpRequest
 * @package Stacc\Recommender\Network
 */
class CurlHttpRequest implements HttpRequestInterface
{

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * CurlHttpRequest constructor.
     * @param Environment $environment
     * @param Logger $logger
     * @param Curl $curl
     */
    public function __construct(Environment $environment, Curl $curl, Logger $logger)
    {
        $this->environment = $environment;
        $this->curl = $curl;
        $this->logger = $logger;
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
            $credentials = $this->environment->getCredentials();

            // Init request
            $this->curl->setOptions(
                [
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_USERPWD => $credentials['id'] . ":" . $credentials['key'],
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_FRESH_CONNECT => 1,
                    CURLOPT_TIMEOUT_MS => $timeout
                ]
            );

            $this->curl->post($url, json_encode($data));
            // Send request
            $output = $this->curl->getBody();
            $httpcode = $this->curl->getStatus();

            if ($httpcode != 200) {
                $this->logger->error("Received error from HTTP request", ['error' => (string)$httpcode]);
            }

            return $output;
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Network/Httprequest->postData() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            return '{"error": "Failed to start a connection!"}';
        }
    }
}
