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
    )
    {
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

        $apps_list_option = $this->optionRepository->findOneBy(['name' => 'EDGEAPPS_LIST']) ?? new Option();
        $apps_list = $apps_list_option->getValue();

        if(!empty($apps_list)) {
            $apps_list = json_decode($apps_list, true);
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
            'tunnel_on' => $tunnel_on
        ]);
    }
}
