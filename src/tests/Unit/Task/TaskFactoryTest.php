<?php

namespace App\Tests\Unit\Task;

use \App\Factory\TaskFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TaskFactoryTest extends TestCase
{

    public function testCreateEnableOnlineTask(): void
    {
        $factory_mock = $this->getTaskFactoryMock();
        $task = $factory_mock->createEnableOnlineTask('test');

        self::assertEquals('enable_online', $task->getTask());
        self::assertEquals(json_encode(['id' => 'test', 'internet_url' => null]), $task->getArgs());
        self::assertEquals(3, $task->getStatus());
    }

    private function getTaskFactoryMock(): TaskFactory
    {
        $optionRepositoryMock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $optionRepositoryMock->method('findOneBy')->will($this->returnValue(null));

        $edgeAppsHelperMock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $edgeAppsHelperMock->method('getInternetUrl')->will($this->returnValue(null));

        // A mock of TaskFactory without modified methods should have done the job (acoording to what I researched), but it does not work.
        // return $this->getMockBuilder(\App\Factory\TaskFactory::class)->setConstructorArgs([$optionRepositoryMock, $edgeAppsHelperMock])->getMock();
        return new TaskFactory($optionRepositoryMock, $edgeAppsHelperMock); // Minor inconvenience... types MockObject don't match TaskFactory constructor but it works!
    }
}
