<?php

namespace App\Factory;

use App\Entity\Task;
use App\Helper\EdgeAppsHelper;
use App\Repository\OptionRepository;

class TaskFactory
{
    public const SETUP_TUNNEL = 'setup_tunnel';
    public const DISABLE_TUNNEL = 'disable_tunnel';
    public const INSTALL_EDGEAPP = 'install_edgeapp';
    public const REMOVE_EDGEAPP = 'remove_edgeapp';
    public const START_EDGEAPP = 'start_edgeapp';
    public const STOP_EDGEAPP = 'stop_edgeapp';
    public const ENABLE_ONLINE = 'enable_online';
    public const DISABLE_ONLINE = 'disable_online';

    private OptionRepository $optionRepository;
    private EdgeAppsHelper $edgeAppsHelper;

    public function __construct(
        OptionRepository $optionRepository,
        EdgeAppsHelper $edgeAppsHelper,
    ) {
        $this->optionRepository = $optionRepository;
        $this->edgeAppsHelper = $edgeAppsHelper;
    }

    public function createErrorTask(string $task_name, string $error_message, string $target = '')
    {
        $task = new Task();
        $task->setTask($task_name);
        if (!empty($target)) {
            $task->setArgs(json_encode(['id' => $target]));
        }
        $task->setStatus(3);
        $task->setResult($error_message);

        return $task;
    }

    public function createSetupTunnelTask(string $bootnode_address, string $bootnode_token, string $assigned_address, string $node_name): Task
    {
        $task = new Task();
        $task->setTask(self::SETUP_TUNNEL);
        $task->setArgs(json_encode([
            'bootnode_address' => $bootnode_address,
            'bootnode_token' => $bootnode_token,
            'assigned_address' => $assigned_address,
            'node_name' => $node_name,
        ]));

        return $task;
    }

    public function createDisableTunnelTask(): Task
    {
        $task = new Task();
        $task->setTask(self::DISABLE_TUNNEL);
        $task->setArgs(json_encode([]));

        return $task;
    }

    public function createInstallEdgeappTask(string $id): Task
    {
        $task = new Task();
        $task->setTask(self::INSTALL_EDGEAPP);
        $task->setArgs(json_encode(['id' => $id]));

        return $task;
    }

    public function createRemoveEdgeappTask(string $id): Task
    {
        $task = new Task();
        $task->setTask(self::REMOVE_EDGEAPP);
        $task->setArgs(json_encode(['id' => $id]));

        return $task;
    }

    public function createStartEdgeappTask(string $id): Task
    {
        $task = new Task();
        $task->setTask(self::START_EDGEAPP);
        $task->setArgs(json_encode(['id' => $id]));

        return $task;
    }

    public function createStopEdgeappTask(string $id): Task
    {
        $task = new Task();
        $task->setTask(self::STOP_EDGEAPP);
        $task->setArgs(json_encode(['id' => $id]));

        return $task;
    }

    public function createEnableOnlineTask(string $id): Task
    {

        $token_option = $this->optionRepository->findOneBy(['name' => 'EDGEBOXIO_API_TOKEN']);

        $internet_url = (null != $token_option) ? $this->edgeAppsHelper->getInternetUrl($token_option->getValue(), $id) : null;

        if (null != $internet_url) {
            $task = new Task();
            $task->setTask(self::ENABLE_ONLINE);
        } else {
            $task = $this->createErrorTask(self::ENABLE_ONLINE, 'Error communicating with the tunnel service.', $id);
        }

        $task->setArgs(json_encode(['id' => $id, 'internet_url' => $internet_url]));

        return $task;
    }

    public function createDisableOnlineTask(string $id): Task
    {
        $task = new Task();
        $task->setTask(self::DISABLE_ONLINE);
        $task->setArgs(json_encode(['id' => $id]));

        return $task;
    }
}
