<?php

namespace App\Controller;

use App\Entity\Option;
use App\Repository\OptionRepository;
use App\Helper\DashboardHelper;
use App\Helper\TunnelHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Require IS_AUTHENTICATED_FULLY for *every* controller method in this class.
 *
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class ApiController extends AbstractController
{

    private OptionRepository $optionRepository;
    private EntityManagerInterface $entityManager;
    private DashboardHelper $dashboardHelper;
    private TunnelHelper $tunnelHelper;

    public function __construct(
        OptionRepository $optionRepository,
        EntityManagerInterface $entityManager,
        DashboardHelper $dashboardHelper,
        TunnelHelper $tunnelHelper
    ) {
        $this->optionRepository = $optionRepository;
        $this->entityManager = $entityManager;
        $this->dashboardHelper = $dashboardHelper;
        $this->tunnelHelper = $tunnelHelper;
    }

    /**
     * @Route("/api", name="api")
     */
    public function index(): JsonResponse
    {
        $data = [
            'status' => 'ok'
        ];
        return new JsonResponse($data);
    }

    /**
     * @Route("/api/settings/dashboard", name="api_settings_dashboard")
     */
    public function settingsDashboard(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            
            // Need to still look at body and such...
            $jsonString = $request->getContent();
            $data = json_decode($jsonString, true);

            $data = $this->dashboardHelper->setSettings($data);

        } else {

            $data = $this->dashboardHelper->getSettings();

        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/settings/tunnel", name="api_settings_tunnel")
     */
    public function settingsTunnel(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            
            // Need to still look at body and such...
            $jsonString = $request->getContent();
            $data = json_decode($jsonString, true);

            if (isset($data['op'])) {
                if ($data['op'] == 'configure') {
                    $data = $this->tunnelHelper->configureTunnel($data);
                } else if ($data['op'] == 'start') {
                    $data = $this->tunnelHelper->startTunnel();
                } else if ($data['op'] == 'stop') {
                    $data = $this->tunnelHelper->stopTunnel();
                } else if ($data['op'] == 'disable') {
                    $data = $this->tunnelHelper->disableTunnel();
                } else {
                    $data = [
                        'status' => 'error',
                        'message' => 'Invalid operation'
                    ];
                }
            } else {
                $data = [
                    'status' => 'error',
                    'message' => 'Invalid operation'
                ];
            }

        } else {

            $data = $this->tunnelHelper->getTunnelStatus();

        }

        return new JsonResponse($data);
    }
}