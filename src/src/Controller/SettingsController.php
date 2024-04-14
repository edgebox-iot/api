<?php

namespace App\Controller;

use App\Entity\Option;
use App\Entity\Task;
use App\Factory\TaskFactory;
use App\Helper\DashboardHelper;
use App\Helper\EdgeAppsHelper;
use App\Helper\EdgeboxioApiConnector;
use App\Helper\ShellHelper;
use App\Helper\SystemHelper;
use App\Helper\TunnelHelper;
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
    private ShellHelper $shellHelper;
    private EntityManagerInterface $entityManager;
    private DashboardHelper $dashboardHelper;
    private TunnelHelper $tunnelHelper;

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
        ShellHelper $shellHelper,
        EntityManagerInterface $entityManager,
        DashboardHelper $dashboardHelper,
        TunnelHelper $tunnelHelper
    ) {
        $this->edgeboxioApiConnector = $edgeboxioApiConnector;
        $this->optionRepository = $optionRepository;
        $this->taskRepository = $taskRepository;
        $this->taskFactory = $taskFactory;
        $this->edgeAppsHelper = $edgeAppsHelper;
        $this->systemHelper = $systemhelper;
        $this->shellHelper = $shellHelper;
        $this->entityManager = $entityManager;
        $this->dashboardHelper = $dashboardHelper;
        $this->tunnelHelper = $tunnelHelper;
    }

    private function setOptionValue(string $name, string $value): void
    {
        $option = $this->optionRepository->findOneBy(['name' => $name]) ?? new Option();
        $option->setName($name);
        $option->setValue($value);
        $this->entityManager->persist($option);
        $this->entityManager->flush();
    }

    #[Route('/settings', name: 'settings')]
    public function index(Request $request): Response
    {
        $status = 'Waiting for Edgebox.io Account Credentials';
        $connection_status = 'Not connected';
        $connection_details = [
            'status' => 'not_connected',
            'message' => 'A connection is not properly established. Please initiate the configuration.',
        ];
        $task_status = 0;
        $alert = [];
        $show_form = false;
        $domain_name = '';
        $domain_name_config_step = 0;
        $release_version = '';

        if ($request->isMethod('post')) {
            // Find out with form to process and call the correct handler, which should return a RedirectResponse

            switch ($request->get('setting')) {
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

            $tunnel_status = $this->tunnelHelper->getTunnelStatus();
            $tunnel_status_code = $tunnel_status['status'];

            $show_form = true;
            $release_version = $this->systemHelper->getReleaseVersion();

            if ('not_configured' != $tunnel_status['status']) {
                // We have a status, which means that a previous tunnel setup was made.
                // We can check the task status.

                $show_form = false;

                if ('error' == $tunnel_status['status']) {
                    $connection_details = [
                        'status' => 'Setup Error',
                        'details' => !empty($tunnel_status['message']) ? $tunnel_status['message'] : 'An unknown error occured. Please try again.',
                    ];
                    $status = 'An error ocurred with the connection to Cloudflare';
                } elseif ('waiting' == $tunnel_status['status']) {
                    $status = 'Waiting for authorization from CloudFlare';
                    $connection_details = [
                        'status' => 'waiting',
                        'details' => 'Please login with your Cloudflare account to finish the setup.',
                        'login_link' => $tunnel_status['login_link'],
                    ];
                } else {
                    $connection_details = $tunnel_status;
                    if (!empty($release_version) && $this->systemHelper::VERSION_CLOUD == $release_version) {
                        $connection_details = [
                            'assigned_address' => $this->systemHelper->getIP(),
                        ];
                    }
                    $status = 'Logged in to CloudFlare ('.$connection_details['status'].')';
                }

                if (!empty($release_version) && $this->systemHelper::VERSION_CLOUD != $release_version) {
                    // Fetch latest SETUP_TUNNEL task to check status
                    $tunnelSetupTask = $this->taskRepository->findOneBy(['task' => TaskFactory::SETUP_TUNNEL], ['id' => 'DESC']);

                    if (null === $tunnelSetupTask) {
                        // Setup task was not found. This is an inconsistent state.
                        $tunnel_setup_status = -1;
                        $connection_details = [
                            'status' => 'Inconsistent',
                            'details' => 'Setup task and status is inconsistent. Try again',
                        ];
                    } else {
                        $tunnel_setup_status = $tunnelSetupTask->getStatus();
                    }

                    switch ($tunnel_setup_status) {
                        case -1:
                            $connection_status = 'Problem with tunnel setup task. Please try again.';
                            break;
                        case 0:
                            // Task has not yet been picked up by edgeboxctl...
                            $connection_status = 'Waiting to start configuration with Cloudflare, please wait';
                            break;

                        case 1:
                            // Task has been picked up by edgeboxctl and is now in progress...
                            $connection_status = 'Configuring cloudflare connection...';
                            break;

                        case 2:
                            // Task is complete and has result. In this, the authentication process with cloudflare is guaranteed to have started
                            // To follow the complete status, we need to check the option TUNNEL_STATUS
                            $connection_status = 'Cloudflare tunnel is connected is active';
                            $tunnel_status_option = $this->optionRepository->findOneBy(['name' => 'TUNNEL_STATUS']) ?? new Option();

                            // Six options here:
                            // - The status is "waiting" but the creation date is older than 5 minutes. This means the authentication process failed. Should be restarted.
                            // - The status is "waiting", which means an authentication process is underway. We should show the link to the user.
                            // - The status is "error", which means the authentication process failed. Should be restarted
                            // - The status is "connected", which means the authentication process was successful and the tunnel is up and running.
                            // - The status is "stopped", which means the tunnel was stopped by the user. Can be started again.
                            // - The status is "not_configured", which means the tunnel is not configured or was disabled. Should be configured.
                            // - The status is "starting", which means authentication was successfull, and the tunnel is starting.

                            $tunnel_status = $tunnel_status_option->getValue();
                            $tunnel_status_creation_date = $tunnel_status_option->getCreated();

                            if (!empty($tunnel_status)) {
                                $tunnel_status = json_decode($tunnel_status, true);
                            } else {
                                $tunnel_status = [];
                            }

                            if (!empty($tunnel_status['status']) && 'waiting' == $tunnel_status['status']) {
                                // Check if the creation date is older than 5 minutes. If so, the authentication process failed.
                                $now = new \DateTime();
                                $now->sub(new \DateInterval('PT5M'));
                                if ($tunnel_status_creation_date < $now) {
                                    // The authentication process failed. Should be restarted.
                                    $connection_status = 'Authentication process failed. Please try again.';
                                    // We can fill in the details of the error here.
                                    $connection_details = [
                                        'status' => 'expired',
                                        'details' => 'The authentication process expired and needs to be restarted. Please try again.',
                                    ];
                                } else {
                                    // The authentication process is underway. Show the link to the user.
                                    $connection_status = 'Waiting for authorization from CloudFlare';
                                }
                            } elseif (!empty($tunnel_status['status']) && 'error' == $tunnel_status['status']) {
                                // The authentication process failed. Should be restarted.
                                $connection_status = 'Authentication process failed. Please try again.';
                                // We can fill in the details of the error here.
                                $connection_details = [
                                    'status' => 'expired',
                                    'details' => 'The authentication process expired and needs to be restarted. Please try again.',
                                ];
                            } elseif (!empty($tunnel_status['status']) && 'connected' == $tunnel_status['status']) {
                                // The authentication process was successful and the tunnel is up and running.
                                $connection_status = 'Cloudflare Tunnel is connected and active 🚀';
                            } elseif (!empty($tunnel_status['status']) && 'stopped' == $tunnel_status['status']) {
                                // The tunnel was stopped by the user. Can be started again.
                                $connection_status = 'Tunnel stopped by user. Can be started again.';
                            } elseif (!empty($tunnel_status['status']) && 'starting' == $tunnel_status['status']) {
                                $connection_status = 'Tunnel is starting. Please wait.';
                            } else {
                                // Unknown status. Should not happen.
                                $connection_status = 'Unknown or inconsistent tunnel status. Please try again.';
                                $connection_details = [
                                    'status' => 'unknown',
                                    'details' => 'Unknown or inconsistent tunnel status. Please try reconfiguring again.',
                                ];
                            }

                            break;

                        default:
                            // Error occurred and should be shown to the user.
                            $connection_status = json_encode($tunnelSetupTask->getResult());
                    }
                } else {
                    $connection_status = 'Feature not available in this release.';
                }
            }

            $options = $this->optionRepository->findOneBy(['name' => 'DOMAIN_NAME']) ?? new Option();
            $domain_name = $options->getValue();

            if (!empty($domain_name) && empty($tunnel_status_code)) {
                // A custom domain was already inserted.
                $domain_name_config_step = 1;
            }

            if (!empty($domain_name) && !empty($tunnel_status_code)) {
                // A custom domain was already inserted and the tunnel setuo was made.
                $domain_name_config_step = 2;
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

        $shell_status_code = $this->shellHelper->getShellStatus()['status'];
        $shell_url = '';
        if ('running' == $shell_status_code) {
            $shell_url = $this->optionRepository->findShellUrl();
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
            'tunnel_status_code' => $tunnel_status_code,
            'shell_status_code' => $shell_status_code,
            'shell_url' => $shell_url,
            'domain_name' => $domain_name,
            'domain_name_config_step' => $domain_name_config_step,
            'apps_online' => $apps_online,
            'apps_list' => $edgeapps_list,
            'ip_address' => $ip_address,
            'release_version' => $release_version,
            'is_dashboard_public' => $is_dashboard_public,
            'dash_internet_url' => $dash_internet_url,
            'dashboard_settings' => $this->dashboardHelper->getSettings(),
        ]);
    }

    #[Route('/settings/logout', name: 'settings_logout')]
    public function logout(): Response
    {
        $this->setOptionValue('EDGEBOXIO_API_TOKEN', '');

        // Issue tasks for SysCtl to setup the tunnel connection to myedge.app service.
        $task = $this->taskFactory->createDisableTunnelTask();
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->redirectToRoute('settings');
    }

    #[Route('//settings/{action}', name: 'settings_action')]
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
            'dashboard_settings' => $this->dashboardHelper->getSettings(),
        ]);
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
