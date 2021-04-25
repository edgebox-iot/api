<?php

namespace App\Controller;

use App\Entity\Option;
use App\Entity\Task;
use App\Helper\EdgeboxioApiConnector;
use App\Repository\OptionRepository;
use App\Repository\TaskRepository;
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
        TaskRepository  $taskRepository,
        EntityManagerInterface $entityManager
    )
    {
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


        if ($request->isMethod('post')) {
            $apiToken = $this->edgeboxioApiConnector->get_token($request->get('username'), $request->get('password'));
            if ($apiToken['status'] == 'success') {
                $this->setOptionValue('EDGEBOXIO_API_TOKEN', $apiToken['value']);

                $tunnelInfo = $this->edgeboxioApiConnector->get_bootnode_info();

                if ($tunnelInfo['status'] == 'success') {
                    // The response was successful. Save fetched information in options and issue setup_tunnel task.
                    $this->setOptionValue('BOOTNODE_ADDRESS', $tunnelInfo['value']['bootnode_address']);
                    $this->setOptionValue('BOOTNODE_TOKEN', $tunnelInfo['value']['bootnode_token']);
                    $this->setOptionValue('BOOTNODE_ASSIGNED_ADDRESS', $tunnelInfo['value']['assigned_address']);
                    $this->setOptionValue('NODE_NAME', $tunnelInfo['value']['node_name']);

                    // Issue tasks for SysCtl to setup the tunnel connection to myedge.app service.
                    $task = new Task();
                    $task->setTask('setup_tunnel');
                    $task->setArgs(json_encode($tunnelInfo['value']));
                    $this->entityManager->persist($task);
                    $this->entityManager->flush();

                    $connection_status = "Configuring tunnel network for ".$tunnelInfo['value']['node_name']."...";
                    $connection_details = $tunnelInfo['value'];

                    $alert = ['category' => 'access', 'type' => 'success', 'message' => 'Login Successful!'];
                }
            }
        } else {

            // GET Request. Should get latest setup_tunnel task status and display it.

            $options = $this->optionRepository->findOneBy(['name' => 'EDGEBOXIO_API_TOKEN']) ?? new Option();
            $api_token = $options->getValue();
            $show_form  = true;

            if (!empty($api_token)) {

                // We have an API token, which means that a previous login and tunnel setup was made.
                // We can check the task status.

                $show_form = false;

                // Is already logged in, and not doing this request through post

                $tunnelInfo = $this->edgeboxioApiConnector->get_bootnode_info($api_token);
                $connection_details = $tunnelInfo['value'];

                $status = "Logged in to Edgebox.io as " . $connection_details['node_name'];

                $tunnelSetupTask = $this->taskRepository->findOneBy(['task' => 'setup_tunnel']);

                switch ( $tunnelSetupTask->getStatus()) {
                    case 0:

                        // Task has not yet been picked up by edgeboxctl...
                        $connection_status = "Waiting for Edgebox to start executing the setup...";
                        break;

                    case 1:

                        // Task has been picked up by edgeboxctl and is not in progress...
                        $connection_status = "Configuring tunnel network for " . $connection_details['node_name'] . "...";
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
            'alert' => $alert,
            'show_form' => $show_form,
            'status' => $status,
            'connection_status' => $connection_status,
            'connection_details' => $connection_details,
            'task_status' => $task_status,
            'api_token' => $api_token,
            'page_title' => 'Settings',
            'page_subtitle' => 'Features & Security'
        ]);
    }
}
