<?php

namespace App\Controller;

use App\Helper\DashboardHelper;
use App\Helper\BackupsHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Require IS_AUTHENTICATED_FULLY for *every* controller method in this class.
 *
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class BackupsController extends AbstractController
{
    private DashboardHelper $dashboardHelper;

    public function __construct(
        DashboardHelper $dashboardHelper,
        BackupsHelper $backupsHelper
    ) {
        $this->dashboardHelper = $dashboardHelper;
        $this->backupsHelper = $backupsHelper;
    }

    /**
     * @Route("/backups", name="backups")
     */
    public function index(): Response
    {
        // var_dump($this->backupsHelper->getBackupsStatus());

        return $this->render('backups/index.html.twig', [
            'controller_title' => 'Backups',
            'controller_subtitle' => 'Safeguard Data',
            'backup_status' => $this->backupsHelper->getBackupsStatus(),
            'dashboard_settings' => $this->dashboardHelper->getSettings(),
            'tunnel_status_code' => '',
        ]);
    }
}
