<?php

namespace App\Controller;

use App\Attribute\RunMiddleware;
use App\Helper\BackupsHelper;
use App\Helper\DashboardHelper;
use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class BackupsController extends BaseController
{
    protected DashboardHelper $dashboardHelper;
    private BackupsHelper $backupsHelper;

    public function __construct(
        DashboardHelper $dashboardHelper,
        BackupsHelper $backupsHelper
    ) {
        $this->dashboardHelper = $dashboardHelper;
        $this->backupsHelper = $backupsHelper;
    }

    #[RunMiddleware('checkChangelogRedirect')]
    #[Route('/backups', name: 'backups')]
    public function index(): Response
    {
        return $this->render('pages/backups/index.html.twig', [
            'controller_title' => 'Backups',
            'controller_subtitle' => 'Safeguard Data',
            'backup_status' => $this->backupsHelper->getBackupsStatus(),
            'dashboard_settings' => $this->dashboardHelper->getSettings(),
        ]);
    }
}
