<?php

namespace App\Factory;

use App\Entity\Task;
use App\Helper\EdgeAppsHelper;
use App\Helper\SystemHelper;
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
    public const ENABLE_PUBLIC_DASHBOARD = 'enable_public_dashboard';
    public const DISABLE_PUBLIC_DASHBOARD = 'disable_public_dashboard';

    private OptionRepository $optionRepository;
    private EdgeAppsHelper $edgeAppsHelper;
    private SystemHelper $systemHelper;

    public function __construct(
        OptionRepository $optionRepository,
        EdgeAppsHelper $edgeAppsHelper,
        SystemHelper $systemHelper
    ) {
        $this->optionRepository = $optionRepository;
        $this->edgeAppsHelper = $edgeAppsHelper;
        $this->systemHelper = $systemHelper;
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
        $domain_option = $this->optionRepository->findOneBy(['name' => 'DOMAIN_NAME']);
        if (null != $domain_option && !empty($domain_option->getValue())) {
            $internet_url = sprintf('%s.%s', $id, $domain_option->getValue());
        } else {
            $token_option = $this->optionRepository->findOneBy(['name' => 'EDGEBOXIO_API_TOKEN']);
            $ip = '';
            if ('cloud' == $this->systemHelper->getReleaseVersion()) {
                // Cloud version does not use bootnode but direct IP instead.
                $ip = $this->systemHelper->getIP();
            }
            $internet_url = (null != $token_option) ? $this->edgeAppsHelper->getInternetUrl($token_option->getValue(), $id, $ip) : null;
        }

        $task = new Task();
        $task->setTask(self::ENABLE_ONLINE);

        if (null === $internet_url) {
            $task->setStatus(Task::STATUS_ERROR);
            $task->setResult('Error comunicating with the Edgebox.io API');
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

    public function createEnablePublicDashboardTask(): Task
    {
        $domain_option = $this->optionRepository->findOneBy(['name' => 'DOMAIN_NAME']);
        if (null != $domain_option && !empty($domain_option->getValue())) {
            $internet_url = sprintf('%s', $domain_option->getValue());
        } else {
            $token_option = $this->optionRepository->findOneBy(['name' => 'EDGEBOXIO_API_TOKEN']);
            $ip = '';
            if ('cloud' == $this->systemHelper->getReleaseVersion()) {
                // Cloud version does not use bootnode but direct IP instead.
                $ip = $this->systemHelper->getIP();
            }
            $id = 'api';
            $internet_url = (null != $token_option) ? $this->edgeAppsHelper->getInternetUrl($token_option->getValue(), $id, $ip) : null;
        }

        $task = new Task();
        $task->setTask(self::ENABLE_PUBLIC_DASHBOARD);
        $task->setArgs(json_encode(['internet_url' => $internet_url]));

        return $task;
    }

    public function createDisablePublicDashboardTask(): Task
    {
        $task = new Task();
        $task->setTask(self::DISABLE_PUBLIC_DASHBOARD);

        return $task;
    }
}
