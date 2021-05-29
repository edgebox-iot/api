<?php

namespace App\Controller;

use App\Entity\Option;
use App\Repository\OptionRepository;
use App\Task\TaskFactory;
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

    /**
     * @var array
     */
    private const ACTION_CONTROLLER_TITLES = [
        'install' => 'Installing app',
        'remove' => 'Removing app',
        'start' => 'Starting app',
        'stop' => 'Stopping app',
        'enable_online' => 'Enabling app online access',
        'disable_online' => 'Disabling app online access',
    ];

    /**
     * @var array
     */
    public const ALLOWED_ACTIONS = [
        'install' => 'createInstallEdgeappTask',
        'remove' => 'createRemoveEdgeappTask',
        'start' => 'createStartEdgeappTask',
        'stop' => 'createStopEdgeappTask',
        'enable_online' => 'createEnableOnlineEdgeappTask',
        'disable_online' => 'createDisableOnlineEdgeappTask',
    ];

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
     * @Route("/edgeapps/{action}/{edgeapp}", name="edgeapp_action")
     */
    public function action(string $action, string $edgeapp): Response
    {
        $controller_title = 'Invalid action';
        $action_result = 'invalid_action';

        $apps_list = $this->getEdgeAppsList();

        $framework_ready = !empty($apps_list);

        $valid_action = !empty(self::ALLOWED_ACTIONS[$action]);
        $edgeapp_exists = $this->edgeAppExists($edgeapp);

        // Before doing anything, validate existance of both a valid action and an existing edgeapp
        if ($valid_action && $edgeapp_exists) {
            $controller_title = self::ACTION_CONTROLLER_TITLES[$action];
            $action_task_factory_method_name = self::ALLOWED_ACTIONS[$action];
            $task = TaskFactory::$action_task_factory_method_name($edgeapp);
            $this->entityManager->persist($task);
            $this->entityManager->flush();
            $action_result = 'executing';
        } elseif ($valid_action && !$edgeapp_exists) {
            $controller_title = 'App not found';
            $action_result = 'edgeapp_not_found';
        }

        return $this->render('edgeapps/action.html.twig', [
            'controller_name' => 'EdgeAppsController',
            'controller_title' => 'EdgeApps - '.$controller_title,
            'controller_subtitle' => 'Please wait...',
            'edgeapp' => $edgeapp,
            'framework_ready' => $framework_ready,
            'result' => $action_result,
            'action' => $action,
        ]);
    }

    private function getEdgeAppsList(): array
    {
        $apps_list_option = $this->optionRepository->findOneBy(['name' => 'EDGEAPPS_LIST']) ?? new Option();

        return json_decode($apps_list_option->getValue(), true);
    }

    private function edgeAppExists(string $app_id): bool
    {
        $found = false;
        $apps_list = $this->getEdgeAppsList();
        if (!empty($apps_list)) {
            foreach ($apps_list as $edge_app) {
                if ($edge_app['id'] == $app_id) {
                    $found = true;

                    return $found;
                }
            }
        }

        return $found;
    }
}
