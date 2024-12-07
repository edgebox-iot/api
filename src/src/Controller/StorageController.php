<?php

namespace App\Controller;

use App\Helper\DashboardHelper;
use App\Helper\StorageHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class StorageController extends AbstractController
{
    private StorageHelper $storageHelper;
    private DashboardHelper $dashboardHelper;

    public function __construct(
        StorageHelper $storageHelper,
        DashboardHelper $dashboardHelper
    ) {
        $this->storageHelper = $storageHelper;
        $this->dashboardHelper = $dashboardHelper;
    }

    #[Route('/storage', name: 'storage')]
    public function index(): Response
    {
        $storage_ready = false;
        $storage_devices_list = $this->storageHelper->humanizeDeviceUsageValues($this->storageHelper->getStorageDevicesList());

        if (!empty($storage_devices_list)) {
            $storage_ready = true;
        }

        return $this->render('pages/storage/index.html.twig', [
            'controller_title' => 'Storage',
            'controller_subtitle' => 'Buckets & Drives',
            'storage_ready' => $storage_ready,
            'storage_devices' => $storage_devices_list,
            'dashboard_settings' => $this->dashboardHelper->getSettings(),
        ]);
    }

    #[Route('/storage/device/new', name: 'storage_device_new')]
    public function storage_device_new(): Response
    {
        return $this->render('pages/storage/device/new.html.twig', [
            'controller_title' => 'Storage',
            'controller_subtitle' => 'Add new device',
            'dashboard_settings' => $this->dashboardHelper->getSettings(),
        ]);
    }
}
