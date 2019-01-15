<?php

namespace Stacc\Recommender\Test\Integration;

use Magento\Framework\App\DeploymentConfig\Reader as DeploymentConfigReader;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\App\DeploymentConfig;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{

    private $moduleName = "Stacc_Recommender";

    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    public function testModuleIsRegistered()
    {
        $registrar = new ComponentRegistrar();

        $paths = $registrar->getPaths(ComponentRegistrar::MODULE);
        $this->assertArrayHasKey($this->moduleName, $paths, "Module '{$this->moduleName}' is not registered!");
    }

    public function testTheModuleIsKnowAndEnabled()
    {
        $moduleList = $this->objectManager->create(ModuleList::class);
        $message = sprintf('The module "%s" is not enabled in the test environment', $this->moduleName);
        $this->assertTrue($moduleList->has($this->moduleName), $message);
    }

    public function testTheModuleIsKnownAndEnabledInTheRealEnvironment()
    {
        $directoryList = $this->objectManager->create(DirectoryList::class,['root'=>BP]);
        $configReader = $this->objectManager->create(DeploymentConfigReader::class, ['directoryList' => $directoryList]);
        $deploymentConfig = $this->objectManager->create(DeploymentConfig::class, ['reader' => $configReader]);

        $moduleList = $this->objectManager->create(ModuleList::class, ['config' => $deploymentConfig]);
        $message = sprintf('The module "%s" is not enabled in the real environment',$this->moduleName);
        $this->assertTrue($moduleList->has($this->moduleName), $message);
    }
}