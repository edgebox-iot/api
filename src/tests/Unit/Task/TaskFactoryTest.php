<?php

namespace App\Tests\Unit\Task;

use App\Task\TaskFactory;
use PHPUnit\Framework\TestCase;

class TaskFactoryTest extends TestCase
{
    public function testCreateDisableTunnelTask(): void
    {
        $task = TaskFactory::createDisableTunnelTask();
        self::assertEquals('disable_tunnel', $task->getTask());
        self::assertEquals('[]', $task->getArgs());
    }
}
