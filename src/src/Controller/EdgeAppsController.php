<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EdgeAppsController extends AbstractController
{
    /**
     * @Route("/edgeapps", name="edgeapps")
     */
    public function index(): Response
    {
        // TODO: Port EdgeApps control logic from src-f3
        return $this->render('not_available.html.twig', [
            'controller_name' => 'EdgeAppsController',
            'controller_title' => 'EdgeApps',
            'controller_subtitle' => 'Applications control',
        ]);
    }
}
