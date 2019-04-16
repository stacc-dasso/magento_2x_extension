<?php

namespace Stacc\Recommender;

use Magento\Framework\ObjectManager\ConfigInterface as ObjectManagerConfig;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Monolog\Handler\StreamHandler;
use Stacc\Recommender\Logger\Logger;
use Stacc\Recommender\Network\CurlHttpRequestInterface;
use Stacc\Recommender\Network\HttpRequestInterface;

/**
 * Class ConfigurationTest
 * @package Stacc\Recommender
 */
class ConfigurationTest extends TestCase
{
    /**
     * @var string
     */
    private $configFileHandlerType = Model\Config\FileHandler\Virtual::class;

    /**
     * @var string
     */
    private $configFileLoggerType = Model\Config\FileLogger\Virtual::class;

    /**
     * @param mixed $expected
     * @param string $type
     * @param string $argumentName
     */
    private function assertDiArgumentSame($expected, $type, $argumentName)
    {
        $arguments = $this->getDiConfig()->getArguments("$type");
        if (!isset($arguments[$argumentName])) {
            $this->fail(sprintf('No argument "%s" configured for "%s"', $argumentName, $type));
        }
        $this->assertSame($expected, $arguments[$argumentName]);
    }

    /**
     * @param string $expected
     * @param string $type
     * @param string $argumentName
     * @param string $arrayArgument
     */
    private function assertDiArgumentArrayInstanceSame($expected, $type, $argumentName, $arrayArgument)
    {
        $arguments = $this->getDiConfig()->getArguments("$type");
        if (!isset($arguments[$argumentName])) {
            $this->fail(sprintf('No argument "%s" configured for "%s"', $argumentName, $type));
        }
        if (!isset($arguments[$argumentName][$arrayArgument])) {
            $this->fail(sprintf('No argument "%s" configured for "%s" argument list', $argumentName, $type));
        }
        if (!isset($arguments[$argumentName][$arrayArgument]["instance"])) {
            $this->fail(sprintf('Argument "%s" for "%s" is not xsi:type="object"', $arrayArgument, $type));
        }
        $this->assertSame($expected, $arguments[$argumentName][$arrayArgument]["instance"]);
    }

    /**
     * @param string $expectedType
     * @param string $type
     */
    private function assertVirtualType($expectedType, $type)
    {
        $this->assertSame($expectedType, $this->getDiConfig()->getInstanceType($type));
    }

    /**
     * @param $expected
     * @param $for
     */
    private function assertPreference($expected, $for)
    {
        $this->assertSame($expected, $this->getDiConfig()->getPreference($for));
    }

    public function testConfigFileLoggerVirtualType()
    {
        $this->assertVirtualType(Logger::class, $this->configFileLoggerType);
        $this->assertDiArgumentSame("FileLogger", $this->configFileLoggerType, "name");
        $this->assertDiArgumentArrayInstanceSame(
            $this->configFileHandlerType,
            $this->configFileLoggerType,
            "handlers",
            "file"
        );
    }

    public function testConfigFileHandlerVirtualType()
    {
        $this->assertVirtualType(StreamHandler::class, $this->configFileHandlerType);
        $this->assertDiArgumentSame("var/log/stacc_unsent.log", $this->configFileHandlerType, "stream");
        $this->assertDiArgumentSame(200, $this->configFileHandlerType, "level");
    }

    public function testConfigHasCorrectHttpRequestPreference()
    {
        $this->assertPreference(CurlHttpRequestInterface::class, HttpRequestInterface::class);
    }

    public function testConfigHasCorrectLoggerPreference()
    {
        $this->assertPreference($this->configFileLoggerType, Logger::class);
    }

    public function testConfigCanBeAccessed()
    {
        $diConfig = ObjectManager::getInstance()->create($this->configFileLoggerType);
        $diExtensionVersion = $diConfig->getExtensionVersion();
        $this->assertRegExp("/^[0-9]?[0-9]\.[0-9]?[0-9]\.[0-9]?[0-9]$/", $diExtensionVersion);
        $this->assertNotEmpty($diExtensionVersion);
    }

    /**
     * @return ObjectManagerConfig
     */
    private function getDiConfig()
    {
        /** @var ObjectManagerConfig $diConfig */
        return ObjectManager::getInstance()->get(ObjectManagerConfig::class);
    }
}
