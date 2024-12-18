<?php

namespace App\Controller;

use App\Attribute\RunMiddleware;
use App\Entity\Option;
use App\Entity\Task;
use App\Factory\TaskFactory;
use App\Helper\BrowserDevHelper;
use App\Helper\DashboardHelper;
use App\Helper\EdgeAppsHelper;
use App\Helper\SystemHelper;
use App\Repository\OptionRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class EdgeAppsController extends BaseController
{
    private OptionRepository $optionRepository;
    private EntityManagerInterface $entityManager;
    private EdgeAppsHelper $edgeAppsHelper;
    private SystemHelper $systemHelper;
    private BrowserDevHelper $browserDevHelper;
    private TaskFactory $taskFactory;
    protected DashboardHelper $dashboardHelper;

    private TaskRepository $taskRepository;

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
        BrowserDevHelper $browserDevHelper,
        TaskFactory $taskFactory,
        TaskRepository $taskRepository,
        DashboardHelper $dashboardHelper
    ) {
        $this->optionRepository = $optionRepository;
        $this->entityManager = $entityManager;
        $this->edgeAppsHelper = $edgeAppsHelper;
        $this->systemHelper = $systemHelper;
        $this->browserDevHelper = $browserDevHelper;
        $this->taskFactory = $taskFactory;
        $this->taskRepository = $taskRepository;
        $this->dashboardHelper = $dashboardHelper;
    }

    #[RunMiddleware('checkChangelogRedirect')]
    #[Route('/edgeapps', name: 'edgeapps')]
    public function index(): Response
    {
        $framework_ready = false;
        $tunnel_on = false;

        $apps_list = $this->edgeAppsHelper->getEdgeAppsList($fetchOngoingStatuses = true, $fetchOngoingStatuses = true);
        $app_tasks = $this->taskRepository->findByOngoing();

        if (!empty($apps_list)) {
            $tunnel_on_option = $this->optionRepository->findOneBy(['name' => 'BOOTNODE_TOKEN']) ?? new Option();
            $tunnel_on = !empty($tunnel_on_option->getValue());
            $framework_ready = true;
        }

        return $this->render('pages/edgeapps/index.html.twig', [
            'controller_title' => 'EdgeApps',
            'controller_subtitle' => 'Applications control',
            'framework_ready' => $framework_ready,
            'release_version' => $this->systemHelper->getReleaseVersion(),
            'apps_list' => $apps_list,
            'app_task_codes' => $app_tasks,
            'is_online_ready' => $this->systemHelper->isOnlineReady(),
            'tunnel_on' => $tunnel_on,
            'dashboard_settings' => $this->dashboardHelper->getSettings(),
        ]);
    }

    #[Route('/edgeapps/details/{edgeapp}', name: 'edgeapp_details')]
    public function details(Request $request, string $edgeapp): Response
    {
        $apps_list = $this->edgeAppsHelper->getEdgeAppsList();
        $framework_ready = !empty($apps_list);

        // We need to find the app with the same id as the one we are looking for. Don't loop through the array, instead leverage PHP for this.
        $edgeapp_config = array_filter($apps_list, function ($app) use ($edgeapp) {
            return $app['id'] === $edgeapp;
        });

        // We need to get the first element of the array, which is the app we are looking for
        $edgeapp_config = array_shift($edgeapp_config);

        // For each arraay element in the $edgeapp_config['options'] array, we need to add a field to prettify the title
        $edgeapp_options = [];
        foreach ($edgeapp_config['options'] as $option) {
            // The title should convert something like "EXAMPLE_TITLE_THING" to "Title thing"
            $title = strtolower(str_replace('_', ' ', $option['key']));
            // Remove first word
            $title = preg_replace('/^[a-z]+/', '', $title);
            // Capitalize first letter
            $title = ucwords($title);
            $option['title'] = $title;
            $edgeapp_options[] = $option;
        }

        // Invert the array ot maintain the order
        // $edgeapp_options = array_reverse($edgeapp_options);

        $edgeapp_config['options'] = $edgeapp_options;

        if ($request->isMethod('post')) {
            // $this->edgeAppsHelper->saveEdgeAppConfig($edgeapp, $request->request->all());
            // We read each field from the form and issue a task to update the config
            //            $task = $this->taskFactory->createUpdateEdgeappConfigTask($edgeapp, $request->request->all());
        } else {
            // Do nothing?
        }

        $logs = [];

        if (!empty($edgeapp_config['services'])) {
            // Fetch the logs for this app for each service
            foreach ($edgeapp_config['services'] as $service) {
                $id = $service['id'];
                try {
                    $service_logs = file_get_contents('/var/www/html/syslogs/'.$id.'.log');
                    $logs[$service['id']] = $service_logs;
                } catch (\ErrorException $e) {
                    // throw $e;
                }
            }
        }

        return $this->render('pages/edgeapps/details.html.twig', [
            'controller_title' => 'EdgeApps',
            'controller_subtitle' => 'Application details',
            'release_version' => $this->systemHelper->getReleaseVersion(),
            'framework_ready' => $framework_ready,
            'is_online_ready' => $this->systemHelper->isOnlineReady(),
            'edgeapp' => $edgeapp_config,
            'edgeapp_logs' => $logs,
            'dashboard_settings' => $this->dashboardHelper->getSettings(),
            'browserdev_status' => $this->browserDevHelper->getBrowserDevStatus(),
        ]);
    }

    #[Route('/edgeapps/{action}/{edgeapp}', name: 'edgeapp_action')]
    public function action(string $action, string $edgeapp): Response
    {
        $task = null;

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

        return $this->render('pages/edgeapps/action.html.twig', [
            'controller_title' => 'EdgeApps - '.$controller_title,
            'controller_subtitle' => 'Please wait...',
            'edgeapp' => $edgeapp,
            'framework_ready' => $framework_ready,
            'result' => $action_result,
            'action' => $action,
            'task' => $task,
            'dashboard_settings' => $this->dashboardHelper->getSettings(),
        ]);
    }
}
