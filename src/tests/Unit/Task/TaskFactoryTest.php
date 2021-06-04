<?php

namespace App\Tests\Unit\Task;

use App\Factory\TaskFactory;
use PHPUnit\Framework\TestCase;

class TaskFactoryTest extends TestCase
{
    public function testCreateEnableOnlineTaskWithoutApiToken(): void
    {
        $optionRepositoryMock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $optionRepositoryMock->method('findOneBy')->will($this->returnValue(null));

        $edgeAppsHelperMock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $edgeAppsHelperMock->method('getInternetUrl')->will($this->returnValue(null));

        $factory = new TaskFactory($optionRepositoryMock, $edgeAppsHelperMock);
        $task = $factory->createEnableOnlineTask('test');

        self::assertEquals('enable_online', $task->getTask());
        self::assertEquals(json_encode(['id' => 'test', 'internet_url' => null]), $task->getArgs());
        self::assertEquals($task::STATUS_ERROR, $task->getStatus());
    }

    public function testCreateEnableOnlineTaskWithUrlFetchFailure(): void
    {
        $optionMock = $this->getMockBuilder(\App\Entity\Option::class)->disableOriginalConstructor()->getMock();
        $optionMock->method('getValue')->will($this->returnValue('test'));

        $optionRepositoryMock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $optionRepositoryMock->method('findOneBy')->will($this->returnValue($optionMock));

        $edgeAppsHelperMock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $edgeAppsHelperMock->method('getInternetUrl')->will($this->returnValue(null));

        $factory = new TaskFactory($optionRepositoryMock, $edgeAppsHelperMock);
        $task = $factory->createEnableOnlineTask('test');

        self::assertEquals('enable_online', $task->getTask());
        self::assertEquals(json_encode(['id' => 'test', 'internet_url' => null]), $task->getArgs());
        self::assertEquals($task::STATUS_ERROR, $task->getStatus());
    }

    public function testCreateEnableOnlineTaskWithValidData(): void
    {
        $optionMock = $this->getMockBuilder(\App\Entity\Option::class)->disableOriginalConstructor()->getMock();
        $optionMock->method('getValue')->will($this->returnValue('test'));

        $optionRepositoryMock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $optionRepositoryMock->method('findOneBy')->will($this->returnValue($optionMock));

        $edgeAppsHelperMock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $edgeAppsHelperMock->method('getInternetUrl')->will($this->returnValue('https://edgebox.io'));

        $factory = new TaskFactory($optionRepositoryMock, $edgeAppsHelperMock);
        $task = $factory->createEnableOnlineTask('test');

        self::assertEquals('enable_online', $task->getTask());
        self::assertEquals(json_encode(['id' => 'test', 'internet_url' => 'https://edgebox.io']), $task->getArgs());
        self::assertEquals($task::STATUS_CREATED, $task->getStatus());
    }
}
