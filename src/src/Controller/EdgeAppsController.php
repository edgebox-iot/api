<?php

namespace App\Controller;

use App\Entity\Option;
use App\Entity\Task;
use App\Factory\TaskFactory;
use App\Helper\EdgeAppsHelper;
use App\Helper\SystemHelper;
use App\Helper\DashboardHelper;
use App\Repository\OptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Require IS_AUTHENTICATED_FULLY for *every* controller method in this class.
 *
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class EdgeAppsController extends AbstractController
{
    private OptionRepository $optionRepository;
    private EntityManagerInterface $entityManager;
    private EdgeAppsHelper $edgeAppsHelper;
    private SystemHelper $systemHelper;
    private TaskFactory $taskFactory;
    private DashboardHelper $dashboardHelper;

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
        'enable_online' => 'createEnableOnlineTask',
        'disable_online' => 'createDisableOnlineTask',
    ];

    public function __construct(
        OptionRepository $optionRepository,
        EntityManagerInterface $entityManager,
        EdgeAppsHelper $edgeAppsHelper,
        SystemHelper $systemHelper,
        TaskFactory $taskFactory,
        DashboardHelper $dashboardHelper
    ) {
        $this->optionRepository = $optionRepository;
        $this->entityManager = $entityManager;
        $this->edgeAppsHelper = $edgeAppsHelper;
        $this->systemHelper = $systemHelper;
        $this->taskFactory = $taskFactory;
        $this->dashboardHelper = $dashboardHelper;
    }

    /**
     * @Route("/edgeapps", name="edgeapps")
     */
    public function index(): Response
    {
        $framework_ready = false;
        $tunnel_on = false;

        $apps_list = $this->edgeAppsHelper->getEdgeAppsList();

        if (!empty($apps_list)) {
            $tunnel_on_option = $this->optionRepository->findOneBy(['name' => 'BOOTNODE_TOKEN']) ?? new Option();
            $tunnel_on = !empty($tunnel_on_option->getValue());
            $framework_ready = true;
        }

        return $this->render('edgeapps/index.html.twig', [
            'controller_title' => 'EdgeApps',
            'controller_subtitle' => 'Applications control',
            'framework_ready' => $framework_ready,
            'release_version' => $this->systemHelper->getReleaseVersion(),
            'apps_list' => $apps_list,
            'is_online_ready' => $this->systemHelper->isOnlineReady(),
            'tunnel_on' => $tunnel_on,
            'dashboard_settings' => $this->dashboardHelper->getSettings()
        ]);
    }

    /**
     * @Route("/edgeapps/{action}/{edgeapp}", name="edgeapp_action")
     */
    public function action(string $action, string $edgeapp): Response
    {
        $controller_title = 'Invalid action';
        $action_result = 'invalid_action';

        $apps_list = $this->edgeAppsHelper->getEdgeAppsList();

        $framework_ready = !empty($apps_list);

        $valid_action = !empty(self::ALLOWED_ACTIONS[$action]);
        $edgeapp_exists = $this->edgeAppsHelper->edgeAppExists($edgeapp);

        // Before doing anything, validate existance of both a valid action and an existing edgeapp
        if ($valid_action && $edgeapp_exists) {
            $controller_title = self::ACTION_CONTROLLER_TITLES[$action];
            $action_task_factory_method_name = self::ALLOWED_ACTIONS[$action];

            $action_result = 'executing';

            $task = $this->taskFactory->$action_task_factory_method_name($edgeapp);

            if (Task::STATUS_ERROR === $task->getStatus()) {
                $action_result = 'error';
            }

            $this->entityManager->persist($task);
            $this->entityManager->flush();
        } elseif ($valid_action && !$edgeapp_exists) {
            $controller_title = 'App not found';
            $action_result = 'edgeapp_not_found';
        }

        return $this->render('edgeapps/action.html.twig', [
            'controller_title' => 'EdgeApps - '.$controller_title,
            'controller_subtitle' => 'Please wait...',
            'edgeapp' => $edgeapp,
            'framework_ready' => $framework_ready,
            'result' => $action_result,
            'action' => $action,
            'dashboard_settings' => $this->dashboardHelper->getSettings()
        ]);
    }
}
