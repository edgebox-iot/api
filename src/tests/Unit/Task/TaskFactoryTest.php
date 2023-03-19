<?php

namespace App\Tests\Unit\Task;

use App\Factory\TaskFactory;
use PHPUnit\Framework\TestCase;

class TaskFactoryTest extends TestCase
{
    public function testCreateSetupTunnelTask(): void
    {
        $option_repository_mock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $edge_apps_helper_mock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $system_helper_mock = $this->getMockBuilder(\App\Helper\SystemHelper::class)->disableOriginalConstructor()->getMock();

        $factory = new TaskFactory($option_repository_mock, $edge_apps_helper_mock, $system_helper_mock);
        $task = $factory->createSetupTunnelTask('test', 'test', 'test', 'test');

        self::assertEquals($factory::SETUP_TUNNEL, $task->getTask());
        self::assertEquals(json_encode([
            'domain_name' => 'test',
        ]), $task->getArgs());
        self::assertEquals($task::STATUS_CREATED, $task->getStatus());
    }

    public function testCreateDisableTunnelTask(): void
    {
        $option_repository_mock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $edge_apps_helper_mock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $system_helper_mock = $this->getMockBuilder(\App\Helper\SystemHelper::class)->disableOriginalConstructor()->getMock();

        $factory = new TaskFactory($option_repository_mock, $edge_apps_helper_mock, $system_helper_mock);
        $task = $factory->createDisableTunnelTask();

        self::assertEquals($factory::DISABLE_TUNNEL, $task->getTask());
        self::assertEquals($task::STATUS_CREATED, $task->getStatus());
    }

    public function testCreateInstallEdgeappTask(): void
    {
        $option_repository_mock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $edge_apps_helper_mock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $system_helper_mock = $this->getMockBuilder(\App\Helper\SystemHelper::class)->disableOriginalConstructor()->getMock();

        $factory = new TaskFactory($option_repository_mock, $edge_apps_helper_mock, $system_helper_mock);
        $task = $factory->createInstallEdgeappTask('test');

        self::assertEquals($factory::INSTALL_EDGEAPP, $task->getTask());
        self::assertEquals(json_encode(['id' => 'test']), $task->getArgs());
        self::assertEquals($task::STATUS_CREATED, $task->getStatus());
    }

    public function testCreateRemoveEdgeappTask(): void
    {
        $option_repository_mock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $edge_apps_helper_mock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $system_helper_mock = $this->getMockBuilder(\App\Helper\SystemHelper::class)->disableOriginalConstructor()->getMock();

        $factory = new TaskFactory($option_repository_mock, $edge_apps_helper_mock, $system_helper_mock);
        $task = $factory->createRemoveEdgeappTask('test');

        self::assertEquals($factory::REMOVE_EDGEAPP, $task->getTask());
        self::assertEquals(json_encode(['id' => 'test']), $task->getArgs());
        self::assertEquals($task::STATUS_CREATED, $task->getStatus());
    }

    public function testCreateStartEdgeappTask(): void
    {
        $option_repository_mock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $edge_apps_helper_mock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $system_helper_mock = $this->getMockBuilder(\App\Helper\SystemHelper::class)->disableOriginalConstructor()->getMock();

        $factory = new TaskFactory($option_repository_mock, $edge_apps_helper_mock, $system_helper_mock);
        $task = $factory->createStartEdgeappTask('test');

        self::assertEquals($factory::START_EDGEAPP, $task->getTask());
        self::assertEquals(json_encode(['id' => 'test']), $task->getArgs());
        self::assertEquals($task::STATUS_CREATED, $task->getStatus());
    }

    public function testCreateStopEdgeappTask(): void
    {
        $option_repository_mock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $edge_apps_helper_mock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $system_helper_mock = $this->getMockBuilder(\App\Helper\SystemHelper::class)->disableOriginalConstructor()->getMock();

        $factory = new TaskFactory($option_repository_mock, $edge_apps_helper_mock, $system_helper_mock);
        $task = $factory->createStopEdgeappTask('test');

        self::assertEquals($factory::STOP_EDGEAPP, $task->getTask());
        self::assertEquals(json_encode(['id' => 'test']), $task->getArgs());
        self::assertEquals($task::STATUS_CREATED, $task->getStatus());
    }

    public function testCreateEnableOnlineTaskWithoutApiToken(): void
    {
        $option_repository_mock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $option_repository_mock->method('findOneBy')->will($this->returnValue(null));

        $edge_apps_helper_mock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $system_helper_mock = $this->getMockBuilder(\App\Helper\SystemHelper::class)->disableOriginalConstructor()->getMock();

        $factory = new TaskFactory($option_repository_mock, $edge_apps_helper_mock, $system_helper_mock);
        $task = $factory->createEnableOnlineTask('test');

        self::assertEquals($factory::ENABLE_ONLINE, $task->getTask());
        self::assertEquals(json_encode(['id' => 'test', 'internet_url' => null]), $task->getArgs());
        self::assertEquals($task::STATUS_ERROR, $task->getStatus());
    }

    public function testCreateEnableOnlineTaskWithUrlFetchFailure(): void
    {
        $option_mock = $this->getMockBuilder(\App\Entity\Option::class)->disableOriginalConstructor()->getMock();

        $option_repository_mock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $option_repository_mock->method('findOneBy')->will($this->returnCallback(
            function ($arg) {
                $option_mock = $this->getMockBuilder(\App\Entity\Option::class)->disableOriginalConstructor()->getMock();
                if ('DOMAIN_NAME' == $arg['name']) {
                    $option_mock->method('getValue')->will($this->returnValue(''));
                } else {
                    $option_mock->method('getValue')->will($this->returnValue('test'));
                }

                return $option_mock;
            }
        ));

        $edge_apps_helper_mock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $edge_apps_helper_mock->method('getInternetUrl')->will($this->returnValue(null));

        $system_helper_mock = $this->getMockBuilder(\App\Helper\SystemHelper::class)->disableOriginalConstructor()->getMock();
        $system_helper_mock->method('getIP')->will($this->returnValue('192.168.1.1'));

        $factory = new TaskFactory($option_repository_mock, $edge_apps_helper_mock, $system_helper_mock);
        $task = $factory->createEnableOnlineTask('test');

        self::assertEquals($factory::ENABLE_ONLINE, $task->getTask());
        self::assertEquals(json_encode(['id' => 'test', 'internet_url' => null]), $task->getArgs());
        self::assertEquals($task::STATUS_ERROR, $task->getStatus());
    }

    public function testCreateEnableOnlineTaskWithValidData(): void
    {
        $option_mock = $this->getMockBuilder(\App\Entity\Option::class)->disableOriginalConstructor()->getMock();

        $option_repository_mock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $option_repository_mock->method('findOneBy')->will($this->returnCallback(
            function ($arg) {
                $option_mock = $this->getMockBuilder(\App\Entity\Option::class)->disableOriginalConstructor()->getMock();
                if ('DOMAIN_NAME' == $arg['name']) {
                    $option_mock->method('getValue')->will($this->returnValue(''));
                } else {
                    $option_mock->method('getValue')->will($this->returnValue('test'));
                }

                return $option_mock;
            }
        ));
        $edge_apps_helper_mock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $edge_apps_helper_mock->method('getInternetUrl')->will($this->returnValue('https://edgebox.io'));

        $system_helper_mock = $this->getMockBuilder(\App\Helper\SystemHelper::class)->disableOriginalConstructor()->getMock();
        $system_helper_mock->method('getIP')->will($this->returnValue('192.168.1.1'));
        $system_helper_mock->method('getReleaseVersion')->will($this->returnValue('prod'));

        $factory = new TaskFactory($option_repository_mock, $edge_apps_helper_mock, $system_helper_mock);
        $task = $factory->createEnableOnlineTask('test');

        self::assertEquals($factory::ENABLE_ONLINE, $task->getTask());
        self::assertEquals(json_encode(['id' => 'test', 'internet_url' => 'https://edgebox.io']), $task->getArgs());
        self::assertEquals($task::STATUS_CREATED, $task->getStatus());
    }

    public function testDisableOnlineTask(): void
    {
        $option_repository_mock = $this->getMockBuilder(\App\Repository\OptionRepository::class)->disableOriginalConstructor()->getMock();
        $edge_apps_helper_mock = $this->getMockBuilder(\App\Helper\EdgeAppsHelper::class)->disableOriginalConstructor()->getMock();
        $system_helper_mock = $this->getMockBuilder(\App\Helper\SystemHelper::class)->disableOriginalConstructor()->getMock();

        $factory = new TaskFactory($option_repository_mock, $edge_apps_helper_mock, $system_helper_mock);
        $task = $factory->createDisableOnlineTask('test');

        self::assertEquals($factory::DISABLE_ONLINE, $task->getTask());
        self::assertEquals(json_encode(['id' => 'test']), $task->getArgs());
        self::assertEquals($task::STATUS_CREATED, $task->getStatus());
    }
}
