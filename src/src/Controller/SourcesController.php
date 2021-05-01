<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SourcesController extends AbstractController
{
    /**
     * @Route("/sources", name="sources")
     */
    public function index(): Response
    {
        return $this->render('not_available.html.twig', [
            'controller_name' => 'SourcesController',
            'controller_title' => 'Sources',
            'controller_subtitle' => 'Automatically pull data',
        ]);
    }
}
