<?php

namespace Stacc\Recommender;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Route\ConfigInterface as RouteConfig;
use Magento\Framework\App\Router\Base as BaseRouter;
use Magento\TestFramework\Request;
use PHPUnit\Framework\TestCase;

class RouteConfigTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @magentoAppArea frontend
     */
    public function testTheModuleRegistersTheStaccRecommenderFrontName()
    {
        /** @var RouteConfig $routeConfig */
        $routeConfig = $this->objectManager->create(RouteConfig::class);
        $this->assertContains("Stacc_Recommender", $routeConfig->getModulesByFrontName("recommender"));
    }

    /**
     * @magentoAppArea frontend
     * @dataProvider actionNameProvider
     */
    public function testTheStaccRecommenderFrontendActionsCanBeFound($actionName, $expected)
    {
        /** @var Request $request */
        $request = $this->objectManager->create(Request::class);
        $request->setModuleName("recommender");
        $request->setControllerName('recommendation');
        $request->setActionName($actionName);

        /** @var BaseRouter $baseRouter */
        $baseRouter = $this->objectManager->create(BaseRouter::class);

        $this->assertInstanceOf($expected, $baseRouter->match($request));
    }

    /**
     * @return array
     */
    public function actionNameProvider()
    {
        return [
            ["check", Controller\Recommendation\Check::class],
            ["get", Controller\Recommendation\Get::class],
            ["logs", Controller\Recommendation\Logs::class],
            ["product", Controller\Recommendation\Product::class],
            ["stores", Controller\Recommendation\Stores::class],
            ["sync", Controller\Recommendation\Sync::class],
        ];
    }
}