<?php

namespace App\Controller;

use App\Entity\Option;
use App\Entity\Task;
use App\Factory\TaskFactory;
use App\Helper\EdgeAppsHelper;
use App\Helper\EdgeboxioApiConnector;
use App\Helper\SystemHelper;
use App\Repository\OptionRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Require IS_AUTHENTICATED_FULLY for *every* controller method in this class.
 *
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class SettingsController extends AbstractController
{
    private EdgeboxioApiConnector $edgeboxioApiConnector;
    private OptionRepository $optionRepository;
    private TaskRepository $taskRepository;
    private TaskFactory $taskFactory;
    private EdgeAppsHelper $edgeAppsHelper;
    private SystemHelper $systemHelper;
    private EntityManagerInterface $entityManager;

    /**
     * @var array
     */
    private const ACTION_CONTROLLER_TITLES = [
        'enable_public_dashboard' => 'Enabling Online Access',
        'disable_public_dashboard' => 'Disabling Online Access',
    ];

    /**
     * @var array
     */
    public const ALLOWED_ACTIONS = [
        'enable_public_dashboard' => 'createEnablePublicDashboardTask',
        'disable_public_dashboard' => 'createDisablePublicDashboardTask',
    ];

    public function __construct(
        EdgeboxioApiConnector $edgeboxioApiConnector,
        OptionRepository $optionRepository,
        TaskRepository $taskRepository,
        TaskFactory $taskFactory,
        EdgeAppsHelper $edgeAppsHelper,
        SystemHelper $systemhelper,
        EntityManagerInterface $entityManager
    ) {
        $this->edgeboxioApiConnector = $edgeboxioApiConnector;
        $this->optionRepository = $optionRepository;
        $this->taskRepository = $taskRepository;
        $this->taskFactory = $taskFactory;
        $this->edgeAppsHelper = $edgeAppsHelper;
        $this->systemHelper = $systemhelper;
        $this->entityManager = $entityManager;
    }

    private function setOptionValue(string $name, string $value): void
    {
        $option = $this->optionRepository->findOneBy(['name' => $name]) ?? new Option();
        $option->setName($name);
        $option->setValue($value);
        $this->entityManager->persist($option);
        $this->entityManager->flush();
    }

    /**
     * @Route("/settings", name="settings")
     */
    public function index(Request $request): Response
    {
        $status = 'Waiting for Edgebox.io Account Credentials';
        $connection_status = 'Not connected';
        $connection_details = [];
        $task_status = 0;
        $alert = [];
        $show_form = false;
        $domain_name = '';
        $domain_name_config_step = 0;
        $release_version = '';

        if ($request->isMethod('post')) {
            // Find out with form to process and call the correct handler, which should return a RedirectResponse

            switch ($request->get('setting')) {
                case 'edgeboxio_login':
                    return $this->handleEdgeboxioLoginSetting($request);
                    break;

                case 'custom_domain':
                    return $this->handleCustomDomainSetting($request);
                    break;

                case 'remove_custom_domain':
                    return $this->handleRemoveCustoMDomainSetting($request);
                    break;

                default:
                    return $this->redirectToRoute('settings');
                    break;
            }
        } else {
            // GET Request. Should get latest setup_tunnel task status and display it.

            $options = $this->optionRepository->findOneBy(['name' => 'EDGEBOXIO_API_TOKEN']) ?? new Option();
            $apiToken = $options->getValue();
            $show_form = true;
            $release_version = $this->systemHelper->getReleaseVersion();

            if (!empty($apiToken)) {
                // We have an API token, which means that a previous login and tunnel setup was made.
                // We can check the task status.

                $show_form = false;

                // Is already logged in, and not doing this request through post

                $tunnelInfo = $this->edgeboxioApiConnector->get_bootnode_info($apiToken);

                if ('error' == $tunnelInfo['status']) {
                    $connection_details = [
                        'node_name' => 'Unavailable',
                        'details' => !empty($tunnelInfo['value']['message']) ? $tunnelInfo['value']['message'] : 'Server could not be reached!'
                    ];
                    $status = 'An error ocurred with communication to Edgebox.io';
                } else {
                    $connection_details = $tunnelInfo['value'];
                    if (!empty($release_version) && $this->systemHelper::VERSION_CLOUD == $release_version) {
                        $connection_details = [
                            'assigned_address' => $this->systemHelper->getIP(),
                            'node_name' => $tunnelInfo['value']['node_name'],
                        ];
                    }
                    $status = 'Logged in to Edgebox.io as '.$connection_details['node_name'];
                }

                if (!empty($release_version) && $this->systemHelper::VERSION_CLOUD != $release_version) {
                    $tunnelSetupTask = $this->taskRepository->findOneBy(['task' => TaskFactory::SETUP_TUNNEL]);

                    if (null === $tunnelSetupTask) {
                        // Setup task was not found. This is an inconsistent state.
                        $tunnel_setup_status = -1;
                    } else {
                        $tunnel_setup_status = $tunnelSetupTask->getStatus();
                    }

                    switch ($tunnel_setup_status) {
                        case -1:
                            $connection_status = 'Problem with tunnel setup task. Please re-login.';
                            break;
                        case 0:
                            // Task has not yet been picked up by edgeboxctl...
                            $connection_status = 'Waiting for Edgebox to start executing the setup...';
                            break;

                        case 1:
                            // Task has been picked up by edgeboxctl and is not in progress...
                            $connection_status = 'Configuring tunnel network for '.$connection_details['node_name'].'...';
                            // TODO: Some sort of auto-reload when the status is this one could be very useful.
                            break;

                        case 2:
                            // Task is complete and has result. In this, case the apps we will allow registration in the myedge.app service.
                            $connection_status = 'Successfully configured myedge.app Service';

                            break;

                        default:
                            // Error occurred and should be shown to the user.
                            $connection_status = json_decode($tunnelSetupTask->getResult())['value'];
                    }
                } else {
                    $connection_status = 'Connected to the myedge.app service.';
                }
            }

            $options = $this->optionRepository->findOneBy(['name' => 'DOMAIN_NAME']) ?? new Option();
            $domain_name = $options->getValue();

            if (!empty($domain_name)) {
                // A custom domain was already inserted.
                $domain_name_config_step = 1;
            }

            $ip_address = $this->systemHelper->getIP();

            // Figure if any of the alerts should trigger...
            if (!empty($request->query->get('alert')) && !empty($request->query->get('type'))) {
                $alert = ['alert' => $request->query->get('alert'), 'type' => $request->query->get('type')];
            }

            $edgeapps_list = $this->edgeAppsHelper->getEdgeAppsList();
            $apps_online = 0;
            foreach ($edgeapps_list as $edgeapp) {
                if ($edgeapp['internet_accessible']) {
                    ++$apps_online;
                }
            }

            $release_version = $this->systemHelper->getReleaseVersion();
            $is_dashboard_public = $this->systemHelper->isDashboardPublic();
            $public_dashboard_option = $this->optionRepository->findOneBy(['name' => 'PUBLIC_DASHBOARD']);
            $dash_internet_url = '';
            if (null != $public_dashboard_option && !empty($public_dashboard_option->getValue())) {
                $dash_internet_url = $public_dashboard_option->getValue();
            }
        }

        return $this->render('settings/index.html.twig', [
            'controller_title' => 'Settings',
            'controller_subtitle' => 'Features & Security',
            'alert' => $alert,
            'show_form' => $show_form,
            'status' => $status,
            'connection_status' => $connection_status,
            'connection_details' => $connection_details,
            'task_status' => $task_status,
            'api_token' => $apiToken,
            'domain_name' => $domain_name,
            'domain_name_config_step' => $domain_name_config_step,
            'apps_online' => $apps_online,
            'apps_list' => $edgeapps_list,
            'ip_address' => $ip_address,
            'release_version' => $release_version,
            'is_dashboard_public' => $is_dashboard_public,
            'dash_internet_url' => $dash_internet_url,
        ]);
    }

    /**
     * @Route("/settings/logout", name="settings_logout")
     */
    public function logout(): Response
    {
        $this->setOptionValue('EDGEBOXIO_API_TOKEN', '');

        // Issue tasks for SysCtl to setup the tunnel connection to myedge.app service.
        $task = $this->taskFactory->createDisableTunnelTask();
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->redirectToRoute('settings');
    }

    /**
     * @Route("/settings/{action}", name="settings_action")
     */
    public function action(string $action): Response
    {
        $controller_title = 'Invalid action';
        $action_result = 'invalid_action';

        $apps_list = $this->edgeAppsHelper->getEdgeAppsList();

        $framework_ready = !empty($apps_list);

        $valid_action = !empty(self::ALLOWED_ACTIONS[$action]);

        if ($valid_action) {
            $controller_title = self::ACTION_CONTROLLER_TITLES[$action];
            $action_task_factory_method_name = self::ALLOWED_ACTIONS[$action];

            $action_result = 'executing';

            $task = $this->taskFactory->$action_task_factory_method_name();

            if (Task::STATUS_ERROR === $task->getStatus()) {
                $action_result = 'error';
            }

            $this->entityManager->persist($task);
            $this->entityManager->flush();
        }

        return $this->render('settings/action.html.twig', [
            'controller_title' => 'Settings - '.$controller_title,
            'controller_subtitle' => 'Please wait...',
            'framework_ready' => $framework_ready,
            'result' => $action_result,
            'action' => $action,
        ]);
    }

    private function handleEdgeboxioLoginSetting(Request $request): RedirectResponse
    {
        $release_version = !empty($this->systemHelper->getReleaseVersion()) ? $this->systemHelper->getReleaseVersion() : $this->systemHelper::VERSION_DEV;

        $apiToken = $this->edgeboxioApiConnector->get_token($request->get('username'), $request->get('password'));
        if ('success' === $apiToken['status']) {
            $this->setOptionValue('EDGEBOXIO_API_TOKEN', $apiToken['value']);

            if ($release_version = !$this->systemHelper::VERSION_CLOUD) {
                $tunnelInfo = $this->edgeboxioApiConnector->get_bootnode_info();

                if ('success' === $tunnelInfo['status']) {
                    // The response was successful. Save fetched information in options and issue setup_tunnel task.
                    $this->setOptionValue('BOOTNODE_ADDRESS', $tunnelInfo['value']['bootnode_address']);
                    $this->setOptionValue('BOOTNODE_TOKEN', $tunnelInfo['value']['bootnode_token']);
                    $this->setOptionValue('BOOTNODE_ASSIGNED_ADDRESS', $tunnelInfo['value']['assigned_address']);
                    $this->setOptionValue('NODE_NAME', $tunnelInfo['value']['node_name']);

                    // Issue tasks for SysCtl to setup the tunnel connection to myedge.app service.
                    $task = $this->taskFactory->createSetupTunnelTask(
                        $tunnelInfo['value']['bootnode_address'],
                        $tunnelInfo['value']['bootnode_token'],
                        $tunnelInfo['value']['assigned_address'],
                        $tunnelInfo['value']['node_name']
                    );
                    $this->entityManager->persist($task);
                    $this->entityManager->flush();

                    $connection_status = 'Configuring tunnel network for '.$tunnelInfo['value']['node_name'].'...';
                    $connection_details = $tunnelInfo['value'];

                    return $this->redirectToRoute('settings', ['alert' => 'edgeboxio_login', 'type' => 'success']);
                }

                // This return means that login was ok but there was an error getting bootnode information.
                return $this->redirectToRoute('settings', ['alert' => 'edgeboxio_login', 'type' => 'error']);
            } else {
                // Logged in successfully, no need to setup bootnode as this will receive direct connections.
                return $this->redirectToRoute('settings', ['alert' => 'edgeboxio_login', 'type' => 'success']);
            }
        }

        // Error Logging in.
        return $this->redirectToRoute('settings', ['alert' => 'edgeboxio_login', 'type' => 'warning']);
    }

    private function handleCustomDomainSetting(Request $request): RedirectResponse
    {
        // TODO: Validate that this is a valid domain name.
        $this->setOptionValue('DOMAIN_NAME', $request->get('domain'));

        return $this->redirectToRoute('settings', ['alert' => 'custom_domain', 'type' => 'success']);
    }

    private function handleRemoveCustomDomainSetting(Request $request): RedirectResponse
    {
        $this->setOptionValue('DOMAIN_NAME', '');

        return $this->redirectToRoute('settings', ['alert' => 'remove_custom_domain', 'type' => 'success']);
    }
}
