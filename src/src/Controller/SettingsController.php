<?php

namespace App\Controller;

use App\Entity\Option;
use App\Entity\Task;
use App\Helper\EdgeboxioApiConnector;
use App\Repository\OptionRepository;
use App\Repository\TaskRepository;
use App\Task\TaskFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SettingsController extends AbstractController
{
    /**
     * @var EdgeboxioApiConnector
     */
    private $edgeboxioApiConnector;

    /**
     * @var OptionRepository
     */
    private $optionRepository;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        EdgeboxioApiConnector $edgeboxioApiConnector,
        OptionRepository $optionRepository,
        TaskRepository $taskRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->edgeboxioApiConnector = $edgeboxioApiConnector;
        $this->optionRepository = $optionRepository;
        $this->taskRepository = $taskRepository;
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

        if ($request->isMethod('post')) {
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
                    $task = TaskFactory::createSetupTunnelTask(
                        $tunnelInfo['value']['bootnode_address'],
                        $tunnelInfo['value']['bootnode_token'],
                        $tunnelInfo['value']['assigned_address'],
                        $tunnelInfo['value']['node_name']
                    );
                    $this->entityManager->persist($task);
                    $this->entityManager->flush();

                    $connection_status = 'Configuring tunnel network for '.$tunnelInfo['value']['node_name'].'...';
                    $connection_details = $tunnelInfo['value'];

                    $alert = ['category' => 'access', 'type' => 'success', 'message' => 'Login Successful!'];
                }
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
                $connection_details = $tunnelInfo['value'];

                $status = 'Logged in to Edgebox.io as '.$connection_details['node_name'];

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
                        $connection_status = 'Successfully connected to myedge.app Service';

                        break;

                    default:
                        // Error occurred and should be shown to the user.
                        $connection_status = json_decode($tunnelSetupTask->getResult())['value'];
                }
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
        ]);
    }

    /**
     * @Route("/settings/logout", name="settings_logout")
     */
    public function logout(): Response
    {
        $this->setOptionValue('EDGEBOXIO_API_TOKEN', '');

        // Issue tasks for SysCtl to setup the tunnel connection to myedge.app service.
        $task = TaskFactory::createDisableTunnelTask();
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->redirectToRoute('settings');
    }
}
