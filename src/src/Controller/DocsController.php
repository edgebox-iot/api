<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DocsController extends AbstractController
{
    /**
     * @Route("/docs", name="docs")
     */
    public function index(): Response
    {
        return $this->render('docs/index.html.twig', [
            'page_title' => 'Documentation',
            'page_subtitle' => 'Documentation',
        ]);
    }
}
