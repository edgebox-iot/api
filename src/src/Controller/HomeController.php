<?php

namespace App\Controller;

use App\Entity\Task;
use App\Helper\EdgeAppsHelper;
use App\Helper\StorageHelper;
use App\Helper\SystemHelper;
use App\Repository\TaskRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Require IS_AUTHENTICATED_FULLY for *every* controller method in this class.
 *
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class HomeController extends AbstractController
{
    private TaskRepository $taskRepository;
    private SystemHelper $systemHelper;
    private EdgeAppsHelper $edgeAppsHelper;
    private StorageHelper $storageHelper;

    public function __construct(
        TaskRepository $taskRepository,
        SystemHelper $systemHelper,
        EdgeAppsHelper $edgeAppsHelper,
        StorageHelper $storageHelper
    ) {
        $this->taskRepository = $taskRepository;
        $this->systemHelper = $systemHelper;
        $this->edgeAppsHelper = $edgeAppsHelper;
        $this->storageHelper = $storageHelper;
    }

    /**
     * @Route("/", name="home")
     */
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_title' => 'Dashboard',
            'controller_subtitle' => 'Welcome back!',
            'container_system_uptime' => $this->getSystemUptimeContainerVar(),
            'container_working_edgeapps' => $this->getWorkingEdgeAppsContainerVars(),
            'container_storage_summary' => $this->getStorageSummaryContainerVars(),
            'container_actions_overview' => $this->getActionsOverviewContainerVars(),
        ]);
    }

    private function getWorkingEdgeAppsContainerVars(): array
    {
        $apps_list = $this->edgeAppsHelper->getEdgeAppsList();

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
                Task::STATUS_CREATED => 'Waiting to configure external access to EdgeApps',
                Task::STATUS_EXECUTING => 'Configuring Online access to EdgeApps',
                Task::STATUS_FINISHED => 'Configured Online access for EdgeApps',
                Task::STATUS_ERROR => 'Failed to configure online access for EdgeApps',
            ],
            'disable_tunnel' => [
                Task::STATUS_CREATED => 'Waiting to disable external access to EdgeApps',
                Task::STATUS_EXECUTING => 'Disabling Online access to EdgeApps',
                Task::STATUS_FINISHED => 'Disabled Online access for EdgeApps',
                Task::STATUS_ERROR => 'Failed to disable online access for EdgeApps',
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
        ];

        $unknown_action_descriptions = [
            Task::STATUS_CREATED => 'Waiting to run action: %s %s',
            Task::STATUS_EXECUTING => 'Running action: %s %s',
            Task::STATUS_FINISHED => 'Action ran: %s %s',
            Task::STATUS_ERROR => 'Failed to run action: %s %s',
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
                $action_description = sprintf($unknown_action_descriptions[$task->getStatus()], $task->getTask(), $task->getArgs());
            } else {
                if (!empty($action_args['id'])) {
                    $action_description = sprintf($action_descriptions[$task->getTask()][$task->getStatus()], $action_args['id']);
                } else {
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
}
