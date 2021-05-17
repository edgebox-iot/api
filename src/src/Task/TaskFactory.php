<?php

namespace App\Task;


use App\Entity\Task;

class TaskFactory
{
    const DISABLE_TUNNEL = 'disable_tunnel';

    public static function createDisableTunnelTask(): Task
    {
        $task = new Task();
        $task->setTask(self::DISABLE_TUNNEL);
        $task->setArgs(json_encode([]));

        return $task;
    }
}
