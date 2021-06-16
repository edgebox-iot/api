<?php

namespace App\Controller;

use App\Entity\Option;
use App\Entity\Task;
use App\Factory\TaskFactory;
use App\Helper\EdgeboxioApiConnector;
use App\Repository\OptionRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class SettingsController extends AbstractController
{
    private EdgeboxioApiConnector $edgeboxioApiConnector;
    private OptionRepository $optionRepository;
    private TaskRepository $taskRepository;
    private TaskFactory $taskFactory;

    private EntityManagerInterface $entityManager;

    public function __construct(
        EdgeboxioApiConnector $edgeboxioApiConnector,
        OptionRepository $optionRepository,
        TaskRepository $taskRepository,
        TaskFactory $taskFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->edgeboxioApiConnector = $edgeboxioApiConnector;
        $this->optionRepository = $optionRepository;
        $this->taskRepository = $taskRepository;
        $this->taskFactory = $taskFactory;
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
        $domain_name = "";
        $domain_name_config_step = 0;

        if ($request->isMethod('post')) {

            // Find out with form to process and call the correct handler, which should return a RedirectResponse

            switch ($request->get('setting')) {
                case 'edgeboxio_login':
                    return $this->handleEdgeboxioLoginSetting($request);
                    break;

                case 'custom_domain':
                    return $this->handleCustomDomainSetting($request);
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

            if (!empty($apiToken)) {
                // We have an API token, which means that a previous login and tunnel setup was made.
                // We can check the task status.

                $show_form = false;

                // Is already logged in, and not doing this request through post

                $tunnelInfo = $this->edgeboxioApiConnector->get_bootnode_info($apiToken);
                if($tunnelInfo['status'] == 'error') {
                    $connection_details = [
                        'node_name' => 'Unavailable',
                        'details' => $tunnelInfo['value']['message']
                    ];
                    $status = 'Logged in to Edgebox.io but a problem is ocurring.';
                } else {
                    $connection_details = $tunnelInfo['value'];
                    $status = 'Logged in to Edgebox.io as '.$connection_details['node_name'];
                }

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
            }
 
            $options =  $this->optionRepository->findOneBy(['name' => 'DOMAIN_NAME']) ?? new Option();
            $domain_name = $options->getValue();

            if(!empty($domain_name)) {

                // A custom domain was already inserted.
                $domain_name_config_step = 1;

            }

            // Figure if any of the alerts should trigger...
            if(!empty($request->query->get('setting')) && !empty($request->query->get('type'))) {
                $alert = ['setting' => $request->query->get('setting'), 'type' => $request->query->get('type')];
            }

        }

        return $this->render('settings/index.html.twig', [
            'controller_name' => 'SettingsController',
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

    private function handleEdgeboxioLoginSetting(Request $request): RedirectResponse 
    {
        $apiToken = $this->edgeboxioApiConnector->get_token($request->get('username'), $request->get('password'));
        if ('success' === $apiToken['status']) {
            $this->setOptionValue('EDGEBOXIO_API_TOKEN', $apiToken['value']);
        
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
        
                return $this->redirectToRoute('settings', ['setting' => 'edgeboxio_login', 'type' => 'success']);
            } 

            return $this->redirectToRoute('settings', ['setting' => 'edgeboxio_login', 'type' => 'warning']);

        }
    }

    private function handleCustomDomainSetting(Request $request): Redirectresponse
    {

        // TODO: Validate that this is a valid domain name.
        $this->setOptionValue('DOMAIN_NAME', $request->get('domain'));
        return $this->redirectToRoute('settings', ['setting' => 'custom_domain', 'type' => 'success']);
    }
}
