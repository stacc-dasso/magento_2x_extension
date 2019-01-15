<?php

namespace Stacc\Recommender\Logger;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Monolog\Formatter\JsonFormatter;

/**
 * Class Logger
 * @package Stacc\Recommender\Logger
 */
class Logger extends \Monolog\Logger
{
    /**
     * Constant for logging channel
     */
    const CHANNEL = "MAGE_2_EXTENSION";

    /**
     * @var
     */
    protected $_version;

    /**
     * Logger constructor.
     * @param JsonFormatter $jsonFormatter
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param string $name
     * @param array $handlers
     */
    public function __construct(
        JsonFormatter $jsonFormatter,
        ComponentRegistrarInterface $componentRegistrar,
        $name,
        array $handlers = array()
    )
    {
        if (array_key_exists("file", $handlers)) {
            $handlers["file"]->setFormatter($jsonFormatter);
        }

        parent::__construct($name, $handlers);

        $path = $componentRegistrar->getPath(ComponentRegistrar::MODULE, 'Stacc_Recommender');
        $composerPath = $path . DIRECTORY_SEPARATOR . 'composer.json';
        $composerConfig = json_decode(file_get_contents($composerPath), true);
        $this->_version = $composerConfig['version'];
    }

    /**
     * @param int $level
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function addRecord($level, $message, array $context = array())
    {
        if (!$this->handlers) {
            $this->pushHandler(new StreamHandler('php://stderr', static::DEBUG));
        }

        $levelName = static::getLevelName($level);

        // check if any handler will handle this message so we can return early and save cycles
        $handlerKey = null;
        foreach ($this->handlers as $key => $handler) {
            if ($handler->isHandling(array('level' => $level))) {
                $handlerKey = $key;
                break;
            }
        }

        if (null === $handlerKey) {
            return false;
        }

        if (!static::$timezone) {
            static::$timezone = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }

        $record = array(
            'channel' => self::CHANNEL,
            'level' => $level,
            'msg' => (string)$message,
            'timestamp' => time(),
            'context' => $context,
            'level_name' => $levelName,
            'extension_version' => $this->_version
        );

        foreach ($this->processors as $processor) {
            $record["processors"] = $processor;
            $record = call_user_func($processor, $record);
        }
        while (isset($this->handlers[$handlerKey]) &&
            false === $this->handlers[$handlerKey]->handle($record)) {
            $handlerKey++;
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function getExtensionVersion()
    {
        return $this->_version;
    }
}