<?php

namespace App\Tests\Unit\Task;

use App\Factory\TaskFactory;
use PHPUnit\Framework\TestCase;

class TaskFactoryTest extends TestCase
{
    public function testCreateEnableOnlineTaskWithoutApiToken(): void
    {
        $option_repository_mock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $option_repository_mock->method('findOneBy')->will($this->returnValue(null));

        $edge_apps_helper_mock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();

        $factory = new TaskFactory($option_repository_mock, $edge_apps_helper_mock);
        $task = $factory->createEnableOnlineTask('test');

        self::assertEquals('enable_online', $task->getTask());
        self::assertEquals(json_encode(['id' => 'test', 'internet_url' => null]), $task->getArgs());
        self::assertEquals($task::STATUS_ERROR, $task->getStatus());
    }

    public function testCreateEnableOnlineTaskWithUrlFetchFailure(): void
    {
        $option_mock = $this->getMockBuilder(\App\Entity\Option::class)->disableOriginalConstructor()->getMock();
        $option_mock->method('getValue')->will($this->returnValue('test'));

        $option_repository_mock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $option_repository_mock->method('findOneBy')->will($this->returnValue($option_mock));

        $edge_apps_helper_mock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $edge_apps_helper_mock->method('getInternetUrl')->will($this->returnValue(null));

        $factory = new TaskFactory($option_repository_mock, $edge_apps_helper_mock);
        $task = $factory->createEnableOnlineTask('test');

        self::assertEquals('enable_online', $task->getTask());
        self::assertEquals(json_encode(['id' => 'test', 'internet_url' => null]), $task->getArgs());
        self::assertEquals($task::STATUS_ERROR, $task->getStatus());
    }

    public function testCreateEnableOnlineTaskWithValidData(): void
    {
        $option_mock = $this->getMockBuilder(\App\Entity\Option::class)->disableOriginalConstructor()->getMock();
        $option_mock->method('getValue')->will($this->returnValue('test'));

        $option_repository_mock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $option_repository_mock->method('findOneBy')->will($this->returnValue($option_mock));

        $edge_apps_helper_mock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $edge_apps_helper_mock->method('getInternetUrl')->will($this->returnValue('https://edgebox.io'));

        $factory = new TaskFactory($option_repository_mock, $edge_apps_helper_mock);
        $task = $factory->createEnableOnlineTask('test');

        self::assertEquals('enable_online', $task->getTask());
        self::assertEquals(json_encode(['id' => 'test', 'internet_url' => 'https://edgebox.io']), $task->getArgs());
        self::assertEquals($task::STATUS_CREATED, $task->getStatus());
    }
}
