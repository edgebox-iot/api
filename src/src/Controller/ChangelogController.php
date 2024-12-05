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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ChangelogController extends AbstractController
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

    #[Route('/changelog', name: 'latest-change-log')]
    public function hello(): Response
    {

        $target_version = $this->dashboardHelper->getSystemChangelogVersion();

        # If LAST_SEEN_CHANGELOG_VERSION is not set, redirect to the changelog page.

        return $this->render('pages/changelog/' . $target_version . '.html.twig', [
            // 'controller_title' => 'Dashboard',
            // 'controller_subtitle' => 'Welcome back!',
            // 'container_system_uptime' => $this->getSystemUptimeContainerVar(),
            // 'container_working_edgeapps' => $this->getWorkingEdgeAppsContainerVars(),
            // 'container_storage_summary' => $this->getStorageSummaryContainerVars(),
            // 'container_backups_last_run' => $this->getLastBackupRunContainerVar(),
            // 'container_actions_overview' => $this->getActionsOverviewContainerVars(),
            // 'container_apps_quickaccess' => $this->getQuickEdgeAppsAccessContainerVars(),
            'target_version' => $target_version,
            'dashboard_settings' => $this->dashboardHelper->getSettings(),
        ]);
    }

    #[Route('/changelog/{version}', name: 'changelog-version')]
    public function changelog_version(string $version): Response
    {
        return $this->render('pages/changelog/' . $version . '.html.twig', [
            // 'controller_title' => 'Dashboard',
            // 'controller_subtitle' => 'Welcome back!',
            // 'container_system_uptime' => $this->getSystemUptimeContainerVar(),
            // 'container_working_edgeapps' => $this->getWorkingEdgeAppsContainerVars(),
            // 'container_storage_summary' => $this->getStorageSummaryContainerVars(),
            // 'container_backups_last_run' => $this->getLastBackupRunContainerVar(),
            // 'container_actions_overview' => $this->getActionsOverviewContainerVars(),
            // 'container_apps_quickaccess' => $this->getQuickEdgeAppsAccessContainerVars(),
            'target_version' => $version,
            'dashboard_settings' => $this->dashboardHelper->getSettings(),
        ]);
    }

}