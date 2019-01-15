<?php

namespace Stacc\Recommender\Model;

use Stacc\Recommender\Network\Apiclient;
use Stacc\Recommender\Network\Environment;
use Stacc\Recommender\Logger\Logger;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filesystem\File\ReadFactory;

/**
 * Class Logdispatcher
 * @package Stacc\Recommender\Model
 */
class Logdispatcher
{

    /**
     *
     */
    const CHANNEL = "MAGE_2_EXTENSION";


    /**
     * @var Apiclient
     */
    protected $_apiclient;

    /**
     * @var Environment
     */
    protected $_environment;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var
     */
    protected $logFile;

    /**
     * @var bool
     */

    protected $_readFactory;

    /**
     * @var bool
     */
    protected $response = false;

    protected $_sentLogs = "";
    /**
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * @var File
     */
    protected $_file;

    /**
     * Logdispatcher constructor.
     * @param Apiclient $apiclient
     * @param Environment $environment
     * @param Logger $logger
     * @param Filesystem $filesystem
     * @param File $file
     * @param ReadFactory $readFactory
     */

    public function __construct(
        Apiclient $apiclient,
        Environment $environment,
        Logger $logger,
        Filesystem $filesystem,
        File $file,
        ReadFactory $readFactory
    )
    {
        $this->_apiclient = $apiclient;
        $this->_environment = $environment;
        $this->_logger = $logger;
        $this->_filesystem = $filesystem;
        $this->_file = $file;
        $this->_readFactory = $readFactory;
    }

    /**
     * Send retrieved log files to STACC
     *
     * @return $this
     */
    public function sendLogs()
    {
        try {
            $logDirectory = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::LOG);
            $this->logFile = $logDirectory->getAbsolutePath('stacc_unsent.log');

            if ($this->_file->fileExists($this->logFile)) {
                $file = fopen($this->logFile, "r");
                $logs = $this->retrieveLogs($file);
                $this->processLogs($file, $logs);
            } else {
                $this->_logger->error("File '$this->logFile' doesn't exist!", array($this->logFile));
            }

        } catch (\Exception $exception) {
            $this->_logger->critical("Model/Logdispatcher->sendLogs() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }
        return $this;
    }

    /**
     * Retrieves logs from stacc_unsent.log
     *
     * @param $io
     * @return array
     */
    public function retrieveLogs($io)
    {
        $logs = array();

        try {
            $i = 0;
            while (($rawLog = fgets($io)) !== false) {
                $log = json_decode(trim($rawLog));
                $log->level = $log->level_name;
                unset($log->level_name);
                $logs[] = $log;
                $i += 1;
            }
            $logs = array_merge([$this->createStartingLog(count($logs))], $logs);
        } catch (\Exception $exception) {
            $this->_logger->critical("Model/Logdispatcher->retrieveLogs() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }

        return $logs;
    }

    /**
     * Processes logs to batches for sending and sends them via apiClient
     *
     * @param $io
     * @param $logs
     */
    private function processLogs($io, $logs)
    {
        try {
            $batchSize = 250;
            $errors = 0;
            $sentSliceSize = 0;
            $logsSize = count($logs) + 1; // Increase log count by added starting and ending message
            $sentLogsCount = 0;
            $sendingCount = 0;
            if ($logsSize > 0) {

                for ($i = 0; $i < $logsSize; $i += $batchSize) {

                    if ($errors > 0) {
                        $this->_logger->error("Failed to send the logs", array("lastBatch" => $sentSliceSize . "/" . $logsSize));
                        $this->setResponse(false);
                        break;
                    }

                    $sendSlice = array_slice($logs, $i, $batchSize);

                    $sendingCount += count($sendSlice);

                    if (count($sendSlice) < $batchSize) {
                        $sendSlice[] = $this->createFinishingLog($sendingCount, $logsSize);
                    }

                    $request = $this->_apiclient->sendLogs($sendSlice);

                    $this->setResponse($request == "{}");

                    if (!$this->getResponse()) {
                        $errors++;
                        $this->_logger->error("No. " . $errors, array($this->logFile));
                    }

                    $sentSliceSize = count($sendSlice);
                    $sentLogsCount += ($request == "{}") ? $sentSliceSize : 0;

                    $this->_sentLogs = $sentLogsCount . "/" . ($logsSize);
                }

                if ($this->getResponse()) {
                    // Remove the log file to prevent duplicating logs

                    fclose($io);
                    unlink($this->logFile);
                } else {
                    fclose($io);
                }
            }
        } catch (\Exception $exception) {
            $this->_logger->critical("Model/Logdispatcher->processLogs() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }
    }

    /**
     * Returns response got from sending data
     *
     * @return bool
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Returns amount of logs sent
     *
     * @return bool
     */
    public function getSentAmount()
    {
        return $this->_sentLogs;
    }

    /**
     * Set query response received from sending data
     *
     * @param $response
     */
    private function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @param $sendSliceSize
     * @param $logsSize
     * @return \stdClass
     */
    private function createFinishingLog($sendSliceSize, $logsSize)
    {
        $message = "Finished sending logs " . ($sendSliceSize + 1) . "/" . ($logsSize);
        $context = ["size" => $sendSliceSize + 1];
        return $this->createInfoLog($message, $context);
    }

    private function createInfoLog($msg, $context = array())
    {
        $sliceEnd = new \stdClass();
        $sliceEnd->channel = self::CHANNEL;
        $sliceEnd->level = "INFO";
        $sliceEnd->msg = $msg;
        $sliceEnd->timestamp = time();
        $sliceEnd->context = $context;
        $sliceEnd->extension_version = $this->_environment->getVersion();
        return $sliceEnd;
    }

    /**
     * @param int $sizeOfLogs
     * @return object
     */
    private function createStartingLog($sizeOfLogs)
    {
        $message = "Sending " . ($sizeOfLogs + 2) . " logs";
        $context = ["size" => ($sizeOfLogs + 2)];
        return $this->createInfoLog($message, $context);
    }
}