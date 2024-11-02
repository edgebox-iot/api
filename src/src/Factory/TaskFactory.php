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
    public const START_SHELL = 'start_shell';
    public const STOP_SHELL = 'stop_shell';
    public const INSTALL_EDGEAPP = 'install_edgeapp';
    public const INSTALL_BULK_EDGEAPPS = 'install_bulk_edgeapps';
    public const REMOVE_EDGEAPP = 'remove_edgeapp';
    public const START_EDGEAPP = 'start_edgeapp';
    public const STOP_EDGEAPP = 'stop_edgeapp';
    public const SET_EDGEAPP_OPTIONS = 'set_edgeapp_options';
    public const SET_EDGEAPP_BASIC_AUTH = 'set_edgeapp_basic_auth';
    public const REMOVE_EDGEAPP_BASIC_AUTH = 'remove_edgeapp_basic_auth';
    public const ENABLE_ONLINE = 'enable_online';
    public const DISABLE_ONLINE = 'disable_online';
    public const ENABLE_PUBLIC_DASHBOARD = 'enable_public_dashboard';
    public const DISABLE_PUBLIC_DASHBOARD = 'disable_public_dashboard';
    public const CHECK_UPDATES = 'check_updates';
    public const APPLY_UPDATES = 'apply_updates';

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
            'repository_password' => $repository_password,
        ]));

        return $task;
    }

    public function createStartBackupTask(): Task
    {
        $task = new Task();
        $task->setTask(self::START_BACKUP);

        return $task;
    }

    public function createRestoreBackupsTask(): Task
    {
        $task = new Task();
        $task->setTask(self::RESTORE_BACKUP);

        return $task;
    }

    public function createDisableBackupsTask(): Task
    {
        $task = new Task();
        $task->setTask(self::DISABLE_BACKUPS);

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

        return $task;
    }

    public function createStartTunnelTask(): Task
    {
        $task = new Task();
        $task->setTask(self::START_TUNNEL);

        return $task;
    }

    public function createStopTunnelTask(): Task
    {
        $task = new Task();
        $task->setTask(self::STOP_TUNNEL);

        return $task;
    }

    public function createStartShellTask(int $timeout): Task
    {
        $task = new Task();
        $task->setTask(self::START_SHELL);
        $task->setArgs(json_encode([
            'timeout' => $timeout,
        ]));

        return $task;
    }

    public function createStopShellTask(): Task
    {
        $task = new Task();
        $task->setTask(self::STOP_SHELL);

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

    public function createSetEdgeappOptionsTask(string $id, array $options): Task
    {
        $task = new Task();
        $task->setTask(self::SET_EDGEAPP_OPTIONS);
        $task->setArgs(json_encode(['id' => $id, 'options' => $options]));

        return $task;
    }

    public function createSetEdgeappBasicAuthTask(string $id, array $login): Task
    {
        $task = new Task();
        $task->setTask(self::SET_EDGEAPP_BASIC_AUTH);
        $task->setArgs(json_encode(['id' => $id, 'login' => $login]));

        return $task;
    }

    public function createInstallBulkEdgeappsTask(array $edgeapps): Task
    {
        $task = new Task();
        $task->setTask(self::INSTALL_BULK_EDGEAPPS);
        $task->setArgs(json_encode(['ids' => $edgeapps]));

        return $task;
    }

    public function createRemoveEdgeappBasicAuthTask(string $id): Task
    {
        $task = new Task();
        $task->setTask(self::REMOVE_EDGEAPP_BASIC_AUTH);
        $task->setArgs(json_encode(['id' => $id]));

        return $task;
    }

    public function createEnableOnlineTask(string $id): Task
    {
        $internetUrl = $this->edgeAppsHelper->getInternetUrl($id);

        $task = new Task();
        $task->setTask(self::ENABLE_ONLINE);

        if (null === $internetUrl) {
            $task->setStatus(Task::STATUS_ERROR);
            $task->setResult('Error communicating with the Edgebox.io API');
        }

        $task->setArgs(json_encode(['id' => $id, 'internet_url' => $internetUrl]));

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
        $internetUrl = $this->edgeAppsHelper->getInternetUrl('api');

        $task = new Task();
        $task->setTask(self::ENABLE_PUBLIC_DASHBOARD);
        $task->setArgs(json_encode(['internet_url' => $internetUrl]));

        return $task;
    }

    public function createDisablePublicDashboardTask(): Task
    {
        $task = new Task();
        $task->setTask(self::DISABLE_PUBLIC_DASHBOARD);

        return $task;
    }

    public function createCheckUpdatesTask(int $timeout): Task
    {
        $task = new Task();
        $task->setTask(self::CHECK_UPDATES);

        return $task;
    }

    public function createApplyUpdatesTask(): Task
    {
        $task = new Task();
        $task->setTask(self::APPLY_UPDATES);

        return $task;
    }
}
