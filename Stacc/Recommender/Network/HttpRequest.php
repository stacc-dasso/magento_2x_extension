<?php

namespace Stacc\Recommender\Network;


/**
 * Interface HttpRequest
 * @package Stacc\Recommender\Network
 */
interface HttpRequest
{
    /**
     * Function sends data to the specified url.
     * Helper function.
     *
     * Params:
     * @param $data
     * @param $url
     * @param int $timeout
     *
     * @return JSON encoded object
     */
    public function postData($data, $url, $timeout);

}