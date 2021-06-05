<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StorageController extends AbstractController
{
    /**
     * @Route("/storage", name="storage")
     */
    public function index(): Response
    {
        return $this->render('storage/index.html.twig', [
            'controller_name' => 'StorageController',
            'controller_title' => 'Storage',
            'controller_subtitle' => 'Buckets & Drives',
        ]);
    }

     /**
     * @Route("/storage/device/new", name="storage_device_new")
     */
    public function storage_device_new(): Response
    {
        return $this->render('storage/device/new.html.twig', [
            'controller_name' => 'StorageController',
            'controller_title' => 'Storage',
            'controller_subtitle' => 'Add new device',
        ]);
    }
}
