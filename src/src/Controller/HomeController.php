<?php

namespace App\Controller;

use App\Helper\EdgeAppsHelper;
use App\Helper\SystemHelper;
use App\Repository\OptionRepository;
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
     * @var SystemHelper
     */
    private $systemHelper;

    /**
     * @var EdgeAppsHelper
     */
    private $edgeAppsHelper;

    public function __construct(
        EdgeAppsHelper $edgeAppsHelper,
        SystemHelper $systemHelper
    ) {
        $this->edgeAppsHelper = $edgeAppsHelper;
        $this->systemHelper = $systemHelper;
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
            'container_system_uptime' => $this->getSystemUptimeContainerVar(),
            'container_working_edgeapps' => $this->getWorkingEdgeAppsContainerVars(),
        ]);
    }

    private function getWorkingEdgeAppsContainerVars()
    {
        $apps_list = $this->edgeAppsHelper->getEdgeAppsList();

        $result = [
            'total' => 0,
            'online' => 0,
        ];

        if (!empty($apps_list)) {
            foreach ($apps_list as $edgeapp) {
                if ('on' == $edgeapp['status']['description']) {
                    ++$result['total'];
                    if ($edgeapp['internet_accessible']) {
                        ++$result['online'];
                    }
                }
            }
        }

        return $result;
    }

    private function getSystemUptimeContainerVar()
    {
        $uptime = $this->systemHelper->getUptimeInSeconds();

        $days = $uptime / (60 * 60 * 24);
        $hours = ($uptime - ($days * 60 * 60 * 24)) / (60 * 60);
        $minutes = (($uptime - ($days * 60 * 60 * 24)) - ($hours * 60 * 60)) / 60;

        if ($days > 0) {
            return (int) $days.' days';
        }

        if ($hours > 0) {
            return (int) $hours.' hours';
        }

        if ($minutes > 0) {
            return (int) $minutes.' minutes';
        }

        return $uptime.' seconds';
    }
}
