<?php

namespace App\Controller;

use App\Helper\StorageHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StorageController extends AbstractController
{
    private StorageHelper $storageHelper;

    public function __construct(
        StorageHelper $storageHelper
    ) {
        $this->storageHelper = $storageHelper;
    }

    /**
     * @Route("/storage", name="storage")
     */
    public function index(): Response
    {
        $storage_ready = false;
        $storage_devices_list = $this->storageHelper->humanizeDeviceUsageValues($this->storageHelper->getStorageDevicesList());

        if (!empty($storage_devices_list)) {
            $storage_ready = true;
        }

        return $this->render('storage/index.html.twig', [
            'controller_title' => 'Storage',
            'controller_subtitle' => 'Buckets & Drives',
            'storage_ready' => $storage_ready,
            'storage_devices' => $storage_devices_list,
        ]);
    }

    /**
     * @Route("/storage/device/new", name="storage_device_new")
     */
    public function storage_device_new(): Response
    {
        return $this->render('storage/device/new.html.twig', [
            'controller_title' => 'Storage',
            'controller_subtitle' => 'Add new device',
        ]);
    }
}
