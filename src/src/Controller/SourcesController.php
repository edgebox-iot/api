<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
* Require IS_AUTHENTICATED_FULLY for *every* controller method in this class.
*
* @IsGranted("IS_AUTHENTICATED_FULLY")
*/
class SourcesController extends AbstractController
{
    /**
     * @Route("/sources", name="sources")
     */
    public function index(): Response
    {
        return $this->render('not_available.html.twig', [
            'controller_title' => 'Sources',
            'controller_subtitle' => 'Automatically pull data',
        ]);
    }
}
