<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Attribute\RunMiddleware;
use App\Entity\Task;
use App\Helper\BackupsHelper;
use App\Helper\DashboardHelper;
use App\Helper\EdgeAppsHelper;
use App\Helper\StorageHelper;
use App\Helper\SystemHelper;
use App\Repository\TaskRepository;
use App\Repository\OptionRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class HomeController extends BaseController
{
    private TaskRepository $taskRepository;
    private OptionRepository $optionRepository;
    private SystemHelper $systemHelper;
    private EdgeAppsHelper $edgeAppsHelper;
    private StorageHelper $storageHelper;
    private BackupsHelper $backupsHelper;
    protected DashboardHelper $dashboardHelper;

    public function __construct(
        TaskRepository $taskRepository,
        OptionRepository $optionRepository,
        SystemHelper $systemHelper,
        EdgeAppsHelper $edgeAppsHelper,
        StorageHelper $storageHelper,
        DashboardHelper $dashboardHelper,
        BackupsHelper $backupsHelper
    ) {
        $this->taskRepository = $taskRepository;
        $this->optionRepository = $optionRepository;
        $this->systemHelper = $systemHelper;
        $this->edgeAppsHelper = $edgeAppsHelper;
        $this->storageHelper = $storageHelper;
        $this->dashboardHelper = $dashboardHelper;
        $this->backupsHelper = $backupsHelper;
    }

    #[Route('/hello', name: 'hello')]
    public function hello(): Response
    {
        return $this->render('home/hello.html.twig', [
            // 'controller_title' => 'Dashboard',
            // 'controller_subtitle' => 'Welcome back!',
            // 'container_system_uptime' => $this->getSystemUptimeContainerVar(),
            // 'container_working_edgeapps' => $this->getWorkingEdgeAppsContainerVars(),
            // 'container_storage_summary' => $this->getStorageSummaryContainerVars(),
            // 'container_backups_last_run' => $this->getLastBackupRunContainerVar(),
            // 'container_actions_overview' => $this->getActionsOverviewContainerVars(),
            // 'container_apps_quickaccess' => $this->getQuickEdgeAppsAccessContainerVars(),
            // 'dashboard_settings' => $this->dashboardHelper->getSettings(),
        ]);
    }

    #[RunMiddleware('checkOnboardingRedirect', 'checkChangelogRedirect')]
    #[Route('/', name: 'home')]
    public function index(): Response
    {

        $actions_overview = $this->getActionsOverviewContainerVars();

        return $this->render('home/index.html.twig', [
            'controller_title' => 'Dashboard',
            'controller_subtitle' => 'Welcome back!',
            'container_system_uptime' => $this->getSystemUptimeContainerVar(),
            'container_working_edgeapps' => $this->getWorkingEdgeAppsContainerVars(),
            'container_storage_summary' => $this->getStorageSummaryContainerVars(),
            'container_backups_last_run' => $this->getLastBackupRunContainerVar(),
            'container_actions_overview' => $actions_overview,
            'container_apps_quickaccess' => $this->getQuickEdgeAppsAccessContainerVars(),
            'dashboard_settings' => $this->dashboardHelper->getSettings(),
        ]);
    }

    private function getWorkingEdgeAppsContainerVars(): array
    {
        $apps_list = $this->edgeAppsHelper->getEdgeAppsList(true, true);

        $result = [
            'total' => 0,
            'online' => 0,
        ];

        if (!empty($apps_list)) {
            foreach ($apps_list as $edgeapp) {
                if ('on' == $edgeapp['status']['description']) {
                    ++$result['total'];
                    if ($edgeapp['internet_accessible']) {
                        ++$result['online'];
                    }
                }
            }
        }

        return $result;
    }

    private function getSystemUptimeContainerVar(): string
    {
        $uptime = $this->systemHelper->getUptimeInSeconds();

        $days = $uptime / (60 * 60 * 24);
        $hours = $uptime / (60 * 60);
        $minutes = $uptime / 60;

        if ($days >= 2) {
            return (int) $days.' days';
        }

        if ($hours >= 2) {
            return (int) $hours.' hours';
        }

        if ($minutes >= 2) {
            return (int) $minutes.' minutes';
        }

        return $uptime.' seconds';
    }

    private function getLastBackupRunContainerVar(): string
    {
        return $this->backupsHelper->getLastBackupRunTime();
    }

    private function getActionsOverviewContainerVars(): array
    {
        $action_descriptions = [
            'install_edgeapp' => [
                Task::STATUS_CREATED => 'Waiting to install %s EdgeApp',
                Task::STATUS_EXECUTING => 'Installing %s EdgeApp...',
                Task::STATUS_FINISHED => 'Installed %s EdgeApp',
                Task::STATUS_ERROR => 'Failed to install %s EdgeApp',
            ],
            'remove_edgeapp' => [
                Task::STATUS_CREATED => 'Waiting to remove %s EdgeApp',
                Task::STATUS_EXECUTING => 'Removing %s EdgeApp...',
                Task::STATUS_FINISHED => 'Removed %s EdgeApp',
                Task::STATUS_ERROR => 'Failed to remove %s EdgeApp',
            ],
            'start_edgeapp' => [
                Task::STATUS_CREATED => 'Waiting to start %s EdgeApp',
                Task::STATUS_EXECUTING => 'Starting %s EdgeApp',
                Task::STATUS_FINISHED => 'Started %s EdgeApp',
                Task::STATUS_ERROR => 'Failed to start %s EdgeApp',
            ],
            'stop_edgeapp' => [
                Task::STATUS_CREATED => 'Waiting to stop %s EdgeApp',
                Task::STATUS_EXECUTING => 'Stopping %s EdgeApp',
                Task::STATUS_FINISHED => 'Stopped %s EdgeApp',
                Task::STATUS_ERROR => 'Failed to stop %s edgeApp',
            ],
            'enable_online' => [
                Task::STATUS_CREATED => 'Waiting to enable online access to %s',
                Task::STATUS_EXECUTING => 'Enabling Online access to %s',
                Task::STATUS_FINISHED => 'Enabled Online access to %s EdgeApp',
                Task::STATUS_ERROR => 'Failed to give online access to %s EdgeApp',
            ],
            'disable_online' => [
                Task::STATUS_CREATED => 'Waiting to restrict online access to %s',
                Task::STATUS_EXECUTING => 'Restricting Online access to %s',
                Task::STATUS_FINISHED => 'Restricted Online access to %s EdgeApp',
                Task::STATUS_ERROR => 'Failed to restrict online access to %s EdgeApp',
            ],
            'setup_tunnel' => [
                Task::STATUS_CREATED => 'Waiting to configure access tunnel',
                Task::STATUS_EXECUTING => 'Configuring access tunnel',
                Task::STATUS_FINISHED => 'Configured access tunnel',
                Task::STATUS_ERROR => 'Failed to configure access tunnel',
            ],
            'start_tunnel' => [
                Task::STATUS_CREATED => 'Waiting to start access tunnel',
                Task::STATUS_EXECUTING => 'Enabling access tunnel',
                Task::STATUS_FINISHED => 'Enabled access tunnel',
                Task::STATUS_ERROR => 'Failed to enable access tunnel',
            ],
            'stop_tunnel' => [
                Task::STATUS_CREATED => 'Waiting to stop access tunnel',
                Task::STATUS_EXECUTING => 'Stopping access tunnel',
                Task::STATUS_FINISHED => 'Stopped access tunnel',
                Task::STATUS_ERROR => 'Problem while stopping access tunnel',
            ],
            'disable_tunnel' => [
                Task::STATUS_CREATED => 'Waiting to disable access tunnel',
                Task::STATUS_EXECUTING => 'Disabling access tunnel',
                Task::STATUS_FINISHED => 'Disabled access tunnel',
                Task::STATUS_ERROR => 'Failed to disable access tunnel',
            ],
            'enable_public_dashboard' => [
                Task::STATUS_CREATED => 'Waiting to enable online access to dashboard',
                Task::STATUS_EXECUTING => 'Enabling Online access to the Dashboard',
                Task::STATUS_FINISHED => 'Enabled Online access to the Dashboard',
                Task::STATUS_ERROR => 'Failed to Enable Online access to the Dashboard',
            ],
            'disable_public_dashboard' => [
                Task::STATUS_CREATED => 'Waiting to disable online access to dashboard',
                Task::STATUS_EXECUTING => 'Disabling Online access to the Dashboard',
                Task::STATUS_FINISHED => 'Disabled Online access to the Dashboard',
                Task::STATUS_ERROR => 'Failed to Disable Online access to the Dashboard',
            ],
            'start_backups' => [
                Task::STATUS_CREATED => 'Waiting to start a manual backup',
                Task::STATUS_EXECUTING => 'Starting manual backup',
                Task::STATUS_FINISHED => 'Started a manual system backup',
                Task::STATUS_ERROR => 'Failed to start a manual backup',
            ],
            'set_edgeapp_options' => [
                Task::STATUS_CREATED => 'Waiting to set options for %s EdgeApp',
                Task::STATUS_EXECUTING => 'Setting options for %s EdgeApp',
                Task::STATUS_FINISHED => 'Updated options for %s EdgeApp',
                Task::STATUS_ERROR => 'Failed to set options for %s EdgeApp',
            ],
            'apply_updates' => [
                Task::STATUS_CREATED => 'Waiting to Update System',
                Task::STATUS_EXECUTING => 'Performing System Update',
                Task::STATUS_FINISHED => 'Performed System Update',
                Task::STATUS_ERROR => 'Failed to Update System',
            ],
            'install_bulk_edgeapps' => [
                Task::STATUS_CREATED => 'Waiting to install EdgeApps: %s',
                Task::STATUS_EXECUTING => 'Bulk Installing EdgeApps: %s',
                Task::STATUS_FINISHED => 'Bulk Installed EdgeApps: %s',
                Task::STATUS_ERROR => 'Failed to bulk install EdgeApps: %s',
            ],
            'start_shell' => [
                Task::STATUS_CREATED => 'Waiting to start interactive shell',
                Task::STATUS_EXECUTING => 'Starting interactive shell',
                Task::STATUS_FINISHED => 'Started an interactive shell',
                Task::STATUS_ERROR => 'Failed to start a shell',
            ],
            'stop_shell' => [
                Task::STATUS_CREATED => 'Waiting to stop interactive shell',
                Task::STATUS_EXECUTING => 'Stopping interactive shell',
                Task::STATUS_FINISHED => 'Stopped interactive shell',
                Task::STATUS_ERROR => 'Failed to stop interactive shell',
            ],
        ];

        $unknown_action_descriptions = [
            Task::STATUS_CREATED => 'Waiting to run action: %s',
            Task::STATUS_EXECUTING => 'Running action: %s',
            Task::STATUS_FINISHED => 'Action ran: %s',
            Task::STATUS_ERROR => 'Failed to run action: %s',
        ];

        $action_icons = [
            'install_edgeapp' => 'spaceship',
            'remove_edgeapp' => 'fat-remove',
            'start_edgeapp' => 'button-play',
            'stop_edgeapp' => 'button-pause',
            'enable_online' => 'planet',
            'disable_online' => 'scissors',
            'setup_tunnel' => 'planet',
            'disable_tunnel' => 'scissors',
            'enable_public_dashboard' => 'ui-04',
            'disable_public_dashboard' => 'ui-04',
            'unknown_action' => 'ui-04',
        ];

        $action_overview_list = [];

        $latest_tasks = $this->taskRepository->getLatestTasks(5);

        foreach ($latest_tasks as $task) {
            $action_args = json_decode($task->getArgs(), true);

            if (empty($action_descriptions[$task->getTask()])) {
                // Indicates an action which is not documented in the descriptions.
                $action_description = sprintf($unknown_action_descriptions[$task->getStatus()], $task->getTask());
            } else {
                if (!empty($action_args['id'])) {
                    $action_description = sprintf($action_descriptions[$task->getTask()][$task->getStatus()], $action_args['id']);
                } elseif (!empty($action_args['ids'])) {
                    $action_description = sprintf($action_descriptions[$task->getTask()][$task->getStatus()], implode(', ', $action_args['ids']));
                }
                else {
                    $action_description = $action_descriptions[$task->getTask()][$task->getStatus()];
                }
            }

            $action_icon = !empty($action_icons[$task->getTask()]) ? $action_icons[$task->getTask()] : $action_icons['unknown_action'];

            switch ($task->getStatus()) {
                case Task::STATUS_CREATED:
                    /*
                        The color css class "warning" will have the task icon be shwon in orange, which is a better mood indicator that something is happening.
                        See https://github.com/edgebox-iot/api/pull/15#discussion_r643806656
                    */
                    $icon_color_class = 'warning';
                    break;
                case Task::STATUS_EXECUTING:
                    $icon_color_class = 'warning';
                    break;
                case Task::STATUS_FINISHED:
                    $icon_color_class = 'success';
                    break;
                case Task::STATUS_ERROR:
                    $icon_color_class = 'danger';
                    break;
                default:
                    $icon_color_class = 'dark';
                    break;
            }

            $action_overview_list[] = [
                'task' => $task,
                'description' => $action_description,
                'last_update' => strtoupper($task->getUpdated()->format('j M g:i A')),
                'icon' => $action_icon,
                'icon_color_class' => $icon_color_class,
            ];
        }

        return $action_overview_list;
    }

    private function getStorageSummaryContainerVars(): array
    {
        $storage_devices_list = $this->storageHelper->getStorageDevicesList();

        if (empty($storage_devices_list)) {
            $result = [
                'percentage' => 'Working...',
                'free' => '',
            ];
        } else {
            $result = $this->storageHelper->getOverallStorageSummary($storage_devices_list);
        }

        return $result;
    }

    private function getQuickEdgeAppsAccessContainerVars(): array
    {
        $apps_list = $this->edgeAppsHelper->getEdgeAppsList(true, true);

        $result = [
            'total' => 0,
            'apps' => [],
        ];

        $valid_statuses = ['on', 'installing', 'stopping', 'starting', 'removing'];

        if (!empty($apps_list)) {
            foreach ($apps_list as $edgeapp) {

                # I have an array $actionsOverview that contains the latest tasks and their statuses
                # The array looks like this:
                // array:5 [▼
                // "task" => App\Entity\Task {#631 ▼
                //     -id: 219
                //     -task: "start_edgeapp"
                //     -args: "{"id":"chatpad"}"
                //     -status: 1
                //     -result: null
                //     -created: DateTime @1730476648 {#548 ▶}
                //     -updated: DateTime @1730480266 {#550 ▶}
                // }
                // "description" => "Starting chatpad EdgeApp"
                // "last_update" => "1 NOV 4:57 PM"
                // "icon" => "button-play"
                // "icon_color_class" => "warning"
                // ]

                // Or can also be like this:
                // array:5 [▼
                // "task" => App\Entity\Task {#631 ▼
                //     -id: 220
                //     -task: "stop_edgeapp"
                //     -args: "{"id":"chatpad"}"
                //     -status: 1
                //     -result: null
                //     -created: DateTime @1730476722 {#548 ▶}
                //     -updated: DateTime @1730480325 {#550 ▶}
                // }
                // "description" => "Stopping chatpad EdgeApp"
                // "last_update" => "1 NOV 4:58 PM"
                // "icon" => "button-pause"
                // "icon_color_class" => "warning"
                // ]

                // When the task args contain the id of the edgeapp, I can use that to check if the edgeapp is being worked on
                // When the status is 1, it means the task is executing
                // In this case, we should set the status to "working"
                
                
                ++$result['total'];
                
                // $status = $edgeapp['status']['description'];
                // if ($this->isEdgeAppBeingWorkedOn($edgeapp['id'], $actionsOverview)) {
                //     $status = 'working';
                // }
            
                $result['apps'][] = [
                    'id' => $edgeapp['id'],
                    'description' => $edgeapp['description'],
                    'url' => $edgeapp['internet_accessible'] ? 'https://' . $edgeapp['internet_url'] : 'http://' . $edgeapp['network_url'],
                    'status' => $edgeapp['status'],
                ];
            }
        }

        return $result;
    }
}
