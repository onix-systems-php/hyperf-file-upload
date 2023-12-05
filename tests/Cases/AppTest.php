<?php

declare(strict_types=1);
/**
 * This file is part of the extension library for Hyperf.
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace OnixSystemsPHP\HyperfFileUpload\Test\Cases;

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Event\EventDispatcher;
use OnixSystemsPHP\HyperfFileUpload\Test\Mocks\TestContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class AppTest extends TestCase
{
    protected ContainerInterface $container;

    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testMain(): void
    {
        $this->assertTrue(true);
    }

    protected function getServiceMock(string $className, int $nRuns): MockObject
    {
        $mock = $this->createMock($className);
        $mock->expects(new InvokedCount($nRuns))->method('run');
        return $mock;
    }

    protected function getEventDispatcherMock(int $nEvents): EventDispatcher|MockObject
    {
        $mock = $this->createMock(EventDispatcher::class);
        $mock->expects(new InvokedCount($nEvents))->method('dispatch');
        return $mock;
    }

    protected function createContainer(array $methods = []): void
    {
        $this->container = new TestContainer();

        foreach ($methods as $key => $value) {
            $this->container->set($key, $value);
        }

        ApplicationContext::setContainer($this->container);
    }
}
