<?php

namespace App\Factory;

use App\Entity\Task;
use App\Helper\EdgeAppsHelper;
use App\Helper\SystemHelper;
use App\Repository\OptionRepository;

class TaskFactory
{
    public const SETUP_BACKUPS = 'setup_backups';
    public const START_BACKUP = 'start_backup';
    public const RESTORE_BACKUP = 'restore_backup';
    public const DISABLE_BACKUPS = 'disable_backups';
    public const SETUP_TUNNEL = 'setup_tunnel';
    public const START_TUNNEL = 'start_tunnel';
    public const STOP_TUNNEL = 'stop_tunnel';
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

    public function createSetupBackupsTask(string $service, string $access_key_id, string $secret_access_key, string $repository_name, string $repository_password): Task
    {
        $task = new Task();
        $task->setTask(self::SETUP_BACKUPS);
        $task->setArgs(json_encode([
            'service' => $service,
            'access_key_id' => $access_key_id,
            'secret_access_key' => $secret_access_key,
            'repository_name' => $repository_name,
            'repository_password' => $repository_password
        ]));

        return $task;
    }

    public function createStartBackupTask(): Task
    {
        $task = new Task();
        $task->setTask(self::START_BACKUP);
        // $task->setArgs(json_encode());

        return $task;
    }

    public function createRestoreBackupTask(): Task
    {
        $task = new Task();
        $task->setTask(self::RESTORE_BACKUP);

        return $task;
    }

    public function createDisableBackupsTask(): Task
    {
        $task = new Task();
        $task->setTask(self::DISABLE_BACKUPS);
        // $task->setArgs(json_encode({}));

        return $task;
    }

    public function createSetupTunnelTask(string $domain_name): Task
    {
        $task = new Task();
        $task->setTask(self::SETUP_TUNNEL);
        $task->setArgs(json_encode(['domain_name' => $domain_name]));

        return $task;
    }

    public function createDisableTunnelTask(): Task
    {
        $task = new Task();
        $task->setTask(self::DISABLE_TUNNEL);
        // $task->setArgs(json_encode({}));

        return $task;
    }

    public function createStartTunnelTask(): Task
    {
        $task = new Task();
        $task->setTask(self::START_TUNNEL);
        // $task->setArgs(json_encode());

        return $task;
    }

    public function createStopTunnelTask(): Task
    {
        $task = new Task();
        $task->setTask(self::STOP_TUNNEL);
        // $task->setArgs(json_encode({}));

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
            if ($this->systemHelper::VERSION_CLOUD == $this->systemHelper->getReleaseVersion()) {
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
            if ($this->systemHelper::VERSION_CLOUD == $this->systemHelper->getReleaseVersion()) {
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
