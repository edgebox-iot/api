<?php

namespace App\Controller;

use App\Entity\Option;
use App\Repository\OptionRepository;
use App\Helper\EdgeAppsHelper;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{

    /**
     * @var OptionRepository
     */
    private $optionRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EdgeAppsHelper
     */
    private $edgeAppsHelper;

    public function __construct(
        OptionRepository $optionRepository,
        EdgeAppsHelper $edgeAppsHelper
    ) {
        $this->optionRepository = $optionRepository;
        $this->edgeAppsHelper = $edgeAppsHelper;
    }


    /**
     * @Route("/", name="home")
     */
    public function index(): Response
    {

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'controller_title' => 'Dashboard',
            'controller_subtitle' => 'Welcome back!',
            'container_working_edgeapps' => $this->getWorkingEdgeAppsContainerVars(),
        ]);
    }

    private function getWorkingEdgeAppsContainerVars() {

        $apps_list = $this->edgeAppsHelper->getEdgeAppsList();

        $result = [
            'total' => 0,
            'online' => 0,
        ];

        if(!empty($apps_list)) {
            foreach ($apps_list as $edgeapp) {
                if ($edgeapp['status']['description'] == 'on') {
                    $result['total']++;
                    if ($edgeapp['internet_accessible']) {
                        $result['online']++;
                    }
                }
            }
        }

        return $result;

    }
}
