<?php

namespace App\Helper;

use App\Entity\Option;
use App\Factory\TaskFactory;
use App\Repository\OptionRepository;
use App\Repository\TaskRepository;

class EdgeAppsHelper
{
    private OptionRepository $optionRepository;

    private TaskRepository $taskRepository;

    private SystemHelper $systemHelper;

    public function __construct(
        OptionRepository $optionRepository,
        TaskRepository $taskRepository,
        SystemHelper $systemHelper
    ) {
        $this->optionRepository = $optionRepository;
        $this->taskRepository = $taskRepository;
        $this->systemHelper = $systemHelper;
    }

    public function getEdgeAppsList(bool $OrderByStatus = true, bool $fetchOngoingStatuses = false): array
    {
        $apps_list_option = $this->optionRepository->findOneBy(['name' => 'EDGEAPPS_LIST']) ?? new Option();

        if (null === $apps_list_option->getValue() || 'null' === $apps_list_option->getValue()) {
            return [];
        }

        $apps = json_decode($apps_list_option->getValue(), true);

        if ($OrderByStatus) {
            // Order by status id so installed and running are displayed first, then stopped, then not installed
            usort($apps, function ($a, $b) {
                return $b['status']['id'] <=> $a['status']['id'];
            });

            // Support for demoting experimentanl non-running apps to the bottom of the list
            // experimental == true and status.id == -1 should be displayed always last
            usort($apps, function ($a, $b) {
                if (-1 == $a['status']['id'] && (!empty($a['experimental'])) && (-1 != $b['status']['id'] || empty($b['experimental']))) {
                    return 1;
                }
                if ((-1 != $a['status']['id'] || empty($a['experimental'])) && -1 == $b['status']['id'] && (!empty($b['experimental']))) {
                    return -1;
                }

                return 0;
            });
        }

        if ($fetchOngoingStatuses) {
            $ongoing_tasks = $this->taskRepository->findByOngoing();

            // die(var_dump($ongoing_tasks));

            $app_tasks = [
                TaskFactory::INSTALL_EDGEAPP,
                TaskFactory::REMOVE_EDGEAPP,
                TaskFactory::START_EDGEAPP,
                TaskFactory::STOP_EDGEAPP,
                TaskFactory::SET_EDGEAPP_OPTIONS,
                TaskFactory::ENABLE_ONLINE,
                TaskFactory::DISABLE_ONLINE,
            ];

            if (!empty($ongoing_tasks)) {
                $ongoing_apps_and_statuses = [];

                foreach ($ongoing_tasks as $ongoing_task) {
                    $task_code = $ongoing_task->getTask();
                    if (in_array($task_code, $app_tasks)) {
                        $app_id = json_decode($ongoing_task->getArgs(), true)['id'];
                        $ongoing_apps_and_statuses[$app_id] = [
                            'task_code' => $task_code,
                            'task_id' => $ongoing_task->getId(),
                        ];
                    } elseif (TaskFactory::INSTALL_BULK_EDGEAPPS == $task_code) {
                        $app_ids = json_decode($ongoing_task->getArgs(), true)['ids'];
                        foreach ($app_ids as $app_id) {
                            $ongoing_apps_and_statuses[$app_id] = [
                                'task_code' => $task_code,
                                'task_id' => $ongoing_task->getId(),
                            ];
                        }
                    }
                }

                foreach ($apps as $app_key => $app) {
                    $app_id = $app['id'];
                    if (!empty($ongoing_apps_and_statuses[$app_id])) {
                        $apps[$app_key]['status'] = [
                            'id' => 4,
                            'description' => $ongoing_apps_and_statuses[$app_id]['task_code'],
                            'task_id' => $ongoing_apps_and_statuses[$app_id]['task_id'],
                        ];
                    }
                }
            }
        }

        return $apps;
    }

    public function edgeAppExists(string $app_id): bool
    {
        $found = false;
        $apps_list = $this->getEdgeAppsList();
        if (!empty($apps_list)) {
            foreach ($apps_list as $edge_app) {
                if ($edge_app['id'] == $app_id) {
                    $found = true;

                    return $found;
                }
            }
        }

        return $found;
    }

    public function getInternetUrl(string $appId): ?string
    {
        $domainName = $this->optionRepository->findDomainName();
        if (null !== $domainName) {
            return sprintf('%s.%s', $appId, $domainName);
        }

        if ($this->systemHelper->isCloud()) {
            $cluster = $this->optionRepository->findCluster();
            $host = $this->optionRepository->findUsername();

            return sprintf('%s-%s.%s', $host, $appId, $cluster);
        }

        return null;
    }
}
