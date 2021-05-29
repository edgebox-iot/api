<?php

namespace App\Task;

use App\Entity\Task;

class TaskFactory
{
    public const DISABLE_TUNNEL = 'disable_tunnel';
    public const INSTALL_EDGEAPP = 'install_edgeapp';
    public const REMOVE_EDGEAPP = 'remove_edgeapp';
    public const START_EDGEAPP = 'start_edgeapp';
    public const STOP_EDGEAPP = 'stop_edgeapp';
    public const ENABLE_ONLINE = 'enable_online';
    public const DISABLE_ONLINE = 'disable_online';

    public static function createDisableTunnelTask(): Task
    {
        $task = new Task();
        $task->setTask(self::DISABLE_TUNNEL);
        $task->setArgs(json_encode([]));
        $task->setStatus(0);



        return $task;
    }

    public static function createInstallEdgeappTask(string $id): Task
    {
        $task = new Task();
        $task->setTask(self::INSTALL_EDGEAPP);
        $task->setArgs(json_encode(['id' => $id]));
        $task->setStatus(0);

        return $task;
    }

    public static function createRemoveEdgeappTask(string $id): Task
    {
        $task = new Task();
        $task->setTask(self::REMOVE_EDGEAPP);
        $task->setArgs(json_encode(['id' => $id]));
        $task->setStatus(0);


        return $task;
    }

    public static function createStartEdgeappTask(string $id): Task
    {
        $task = new Task();
        $task->setTask(self::START_EDGEAPP);
        $task->setArgs(json_encode(['id' => $id]));
        $task->setStatus(0);


        return $task;
    }

    public static function createStopEdgeappTask(string $id): Task
    {
        $task = new Task();
        $task->setTask(self::STOP_EDGEAPP);
        $task->setArgs(json_encode(['id' => $id]));
        $task->setStatus(0);


        return $task;
    }

    public static function createEnableOnlineTask(string $id): Task
    {
        $task = new Task();
        $task->setTask(self::ENABLE_ONLINE);
        $task->setArgs(json_encode(['id' => $id]));
        $task->setStatus(0);


        return $task;
    }

    public static function createDisableOnlineTask(string $id): Task
    {
        $task = new Task();
        $task->setTask(self::DISABLE_ONLINE);
        $task->setArgs(json_encode(['id' => $id]));
        $task->setStatus(0);

        return $task;
    }
}
