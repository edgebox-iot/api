<?php

namespace App\Controller;

use App\Entity\Option;
use App\Repository\OptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EdgeAppsController extends AbstractController
{
    /**
     * @var OptionRepository
     */
    private $optionRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        OptionRepository $optionRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->optionRepository = $optionRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/edgeapps", name="edgeapps")
     */
    public function index(): Response
    {
        $framework_ready = false;
        $apps_list = [];
        $tunnel_on = false;

        $apps_list = $this->getEdgeAppsList();

        if (!empty($apps_list)) {
            $tunnel_on_option = $this->optionRepository->findOneBy(['name' => 'BOOTNODE_TOKEN']) ?? new Option();
            $tunnel_on = !empty($tunnel_on_option->getValue());
            $framework_ready = true;
        }

        // TODO: Port EdgeApps control logic from src-f3
        return $this->render('edgeapps/index.html.twig', [
            'controller_name' => 'EdgeAppsController',
            'controller_title' => 'EdgeApps',
            'controller_subtitle' => 'Applications control',
            'framework_ready' => $framework_ready,
            'apps_list' => $apps_list,
            'tunnel_on' => $tunnel_on,
        ]);
    }

    /**
     * @Route("/edgeapps/start/{edgeapp}", name="edgeapp_start")
     */
    public function start(string $edgeapp): Response
    {
        $framework_ready = !empty($this->getEdgeAppsList());

        return $this->render('edgeapps/action.html.twig', [
            'controller_name' => 'EdgeAppsController',
            'controller_title' => 'EdgeApps - Starting App',
            'controller_subtitle' => 'Please wait...',
            'edgeapp' => $edgeapp,
            'framework_ready' => $framework_ready,
            'result' => 'executing',
            'action' => 'start',
        ]);
    }

    /**
     * @Route("/edgeapps/{action}/{edgeapp}", name="edgeapp_stop")
     */
    public function stop(string $edgeapp): Response
    {
        $framework_ready = !empty($this->getEdgeAppsList());

        return $this->render('edgeapps/action.html.twig', [
            'controller_name' => 'EdgeAppsController',
            'controller_title' => 'EdgeApps - Stopping App',
            'controller_subtitle' => 'Please wait...',
            'edgeapp' => $edgeapp,
            'framework_ready' => $framework_ready,
            'result' => 'executing',
            'action' => 'stop',
        ]);
    }

    private function getEdgeAppsList(): array
    {
        $apps_list_option = $this->optionRepository->findOneBy(['name' => 'EDGEAPPS_LIST']) ?? new Option();

        return $apps_list = json_decode($apps_list_option->getValue(), true);
    }
}
