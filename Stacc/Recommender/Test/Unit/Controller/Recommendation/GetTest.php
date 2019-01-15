<?php

namespace Stacc\Recommender\Controller\Recommendation;

use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\HTTP\PhpEnvironment\Request as HttpRequest;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Model\Layout\Merge;
use Magento\Framework\View\Layout\ProcessorInterface;
use PHPUnit\Framework\TestCase;
use Stacc\Recommender\Logger\Logger;

class GetTest extends TestCase
{
    /**
     * @var
     */
    private $controller;

    private $mockRequest;

    protected function setUp()
    {
        $this->mockRequest = $this->createMock(HttpRequest::class);
        $mockContext = $this->createMock(ActionContext::class);
        $mockLogger = $this->createMock(Logger::class);
        $mockLayout = $this->createMock(Layout::class);
        $mockProcessFactory = $this->createMock(ProcessorInterface::class);
        $mockMerge = $this->createMock(Merge::class);
        $mockLayout->expects($this->once())->method('getUpdate')->willReturn($mockProcessFactory);
        $mockProcessFactory->expects($this->once())->method('addHandle')->willReturn($mockMerge);
        $this->controller = new Get($mockLogger, $mockLayout, $mockContext);
    }

    public function testGetRequestMethodExists()
    {
        $this->assertTrue(method_exists($this->controller, "getRequest"));
    }
}