<?php

namespace App\Controller;

use App\Entity\Task;
use App\Factory\TaskFactory;
use App\Helper\BackupsHelper;
use App\Helper\DashboardHelper;
use App\Helper\EdgeAppsHelper;
use App\Helper\TunnelHelper;
use App\Repository\OptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Require IS_AUTHENTICATED_FULLY for *every* controller method in this class.
 *
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class ApiController extends AbstractController
{
    private OptionRepository $optionRepository;
    private EntityManagerInterface $entityManager;
    private DashboardHelper $dashboardHelper;
    private TunnelHelper $tunnelHelper;
    private BackupsHelper $backupsHelper;
    private EdgeAppsHelper $edgeAppsHelper;
    private TaskFactory $taskFactory;

    public function __construct(
        OptionRepository $optionRepository,
        EntityManagerInterface $entityManager,
        DashboardHelper $dashboardHelper,
        TunnelHelper $tunnelHelper,
        BackupsHelper $backupsHelper,
        EdgeAppsHelper $edgeAppsHelper,
        TaskFactory $taskFactory
    ) {
        $this->optionRepository = $optionRepository;
        $this->entityManager = $entityManager;
        $this->dashboardHelper = $dashboardHelper;
        $this->tunnelHelper = $tunnelHelper;
        $this->backupsHelper = $backupsHelper;
        $this->edgeAppsHelper = $edgeAppsHelper;
        $this->taskFactory = $taskFactory;
    }

    /**
     * @Route("/api", name="api")
     */
    public function index(): JsonResponse
    {
        $data = [
            'status' => 'ok',
        ];

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/settings/dashboard", name="api_settings_dashboard")
     */
    public function settingsDashboard(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            // Need to still look at body and such...
            $jsonString = $request->getContent();
            $data = json_decode($jsonString, true);

            $data = $this->dashboardHelper->setSettings($data);
        } else {
            $data = $this->dashboardHelper->getSettings();
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/settings/tunnel", name="api_settings_tunnel")
     */
    public function settingsTunnel(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            // Need to still look at body and such...
            $jsonString = $request->getContent();
            $data = json_decode($jsonString, true);

            if (isset($data['op'])) {
                if ('configure' == $data['op'] && isset($data['domain_name'])) {
                    $data = $this->tunnelHelper->configureTunnel($data['domain_name']);
                } elseif ('start' == $data['op']) {
                    $data = $this->tunnelHelper->startTunnel();
                } elseif ('stop' == $data['op']) {
                    $data = $this->tunnelHelper->stopTunnel();
                } elseif ('disable' == $data['op']) {
                    $data = $this->tunnelHelper->disableTunnel();
                } else {
                    $data = [
                        'status' => 'error',
                        'message' => 'Invalid operation',
                    ];
                }
            } else {
                $data = [
                    'status' => 'error',
                    'message' => 'Invalid operation',
                ];
            }
        } else {
            $data = $this->tunnelHelper->getTunnelStatus();
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/backups", name="api_backups")
     */
    public function backups(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            // Need to still look at body and such...
            $jsonString = $request->getContent();
            $data = json_decode($jsonString, true);

            if (isset($data['op'])) {
                if ('remove' != $data['op'] && 'stop' != $data['op'] && $this->backupsHelper->isBackupsRunning()) {
                    $data['op'] = 'working';
                }

                if ('configure' == $data['op'] && $this->backupsHelper->validateArgs($data)) {
                    $data = $this->backupsHelper->configureBackups(
                        $data['service'],
                        $data['access_key_id'],
                        $data['secret_access_key'],
                        $data['repository_name'],
                        $data['repository_password'],
                    );
                } elseif ('backup' == $data['op']) {
                    $data = $this->backupsHelper->startBackup();
                } elseif ('stop' == $data['op']) {
                    $data = $this->backupsHelper->stopBackup();
                } elseif ('disable' == $data['op']) {
                    $data = $this->backupsHelper->disableBackups();
                } elseif ('restore' == $data['op']) {
                    $data = $this->backupsHelper->restoreBackups();
                } elseif ('remove' == $data['op']) {
                    $data = $this->backupsHelper->removeBackupsConfig();
                } elseif ('working' == $data['op']) {
                    $data = [
                        'status' => 'error',
                        'message' => 'The Backups System is currently doing some work. Try again later.',
                    ];
                } else {
                    $data = [
                        'status' => 'error',
                        'message' => 'Invalid operation',
                    ];
                }
            } else {
                $data = [
                    'status' => 'error',
                    'message' => 'Invalid operation',
                ];
            }
        } else {
            $data = $this->backupsHelper->getBackupsStatus();
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/tasks", name="api_tasks")
     */
    public function tasks(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            // Need to still look at body and such...
            $jsonString = $request->getContent();
            $data = json_decode($jsonString, true);

            if (isset($data['op'])) {
                if ('remove' == $data['op'] && isset($data['id'])) {
                    $task = $this->entityManager->getRepository(Task::class)->find($data['id']);

                    if (null === $task) {
                        $data = [
                            'status' => 'error',
                            'message' => 'Task not found',
                        ];
                    } else {
                        $this->entityManager->remove($task);
                        $this->entityManager->flush();

                        $data = [
                            'status' => 'ok',
                            'message' => 'Task removed',
                        ];
                    }
                } else {
                    $data = [
                        'status' => 'error',
                        'message' => 'Invalid operation',
                    ];
                }
            } else {
                $data = [
                    'status' => 'error',
                    'message' => 'Invalid operation',
                ];
            }
        } else {
            $tasks = $this->entityManager->getRepository(Task::class)->findAll();
            $data = [];
            foreach ($tasks as $task) {
                $data[] = [
                    'id' => $task->getId(),
                    'task' => $task->getTask(),
                    'args' => $task->getArgs(),
                    'status' => $task->getStatus(),
                    'result' => $task->getResult(),
                    'created' => $task->getCreated(),
                ];
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/tasks/{id}", name="api_tasks_id")
     */
    public function tasks_id(Request $request, int $id): JsonResponse
    {
        if ($request->isMethod('post')) {
            // Need to still look at body and such...
            $jsonString = $request->getContent();
            $data = json_decode($jsonString, true);

            if (isset($data['op'])) {
                if ('remove' == $data['op']) {
                    $task = $this->entityManager->getRepository(Task::class)->find($id);

                    if (null === $task) {
                        $data = [
                            'status' => 'error',
                            'message' => 'Task not found',
                        ];
                    } else {
                        $this->entityManager->remove($task);
                        $this->entityManager->flush();

                        $data = [
                            'status' => 'ok',
                            'message' => 'Task removed',
                        ];
                    }
                } else {
                    $data = [
                        'status' => 'error',
                        'message' => 'Invalid operation',
                    ];
                }
            } else {
                $data = [
                    'status' => 'error',
                    'message' => 'Invalid operation',
                ];
            }
        } else {
            // Find the task with id=$id
            $task = $this->entityManager->getRepository(Task::class)->find($id);
            $data = [
                'id' => $task->getId(),
                'task' => $task->getTask(),
                'args' => $task->getArgs(),
                'status' => $task->getStatus(),
                'result' => $task->getResult(),
                'created' => $task->getCreated(),
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/edgeapps", name="api_edgeapps")
     */
    public function edgeapps(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            $jsonString = $request->getContent();
            $data = json_decode($jsonString, true);

            if (isset($data['op'])) {
                if ('options' == $data['op'] && isset($data['id'])) {
                    $response = [
                        'status' => 'error',
                        'message' => 'App not found',
                    ];

                    if ($this->edgeAppsHelper->edgeAppExists($data['id'])) {
                        $apps_list = $this->edgeAppsHelper->getEdgeAppsList();
                        // die(var_dump($apps_list));
                        // $current_options = $apps_list[$data['id']]['options'];

                        // Find the array entry that is the app we are looking for
                        // Entries in the array are like:
                        // ['id' => 'app_id', 'options' => ['key' => 'value']]

                        $edgeapp = null;
                        foreach ($apps_list as $app) {
                            if ($app['id'] == $data['id']) {
                                $edgeapp = $app;
                                break;
                            }
                        }

                        $current_options = $edgeapp['options'];

                        // Check if all keys in the $data array exist as keys in the $current_options array.
                        // If not, error out.
                        $data_keys = array_keys($data['options']);
                        // die(var_dump($data_keys));

                        // current_options has the following format
                        // [['key' => 'option_key', 'value' => 'test', ...], ...]
                        $current_options_keys = [];
                        foreach ($current_options as $key => $option) {
                            $current_options_keys[] = $option['key'];
                        }

                        $diff = array_diff($data_keys, $current_options_keys);
                        if (!empty($diff)) {
                            // die(var_dump($diff));
                            $response = [
                                'status' => 'error',
                                'message' => 'Invalid options',
                            ];
                        } else {
                            // If all keys exist, then convert options to task format and issue a task
                            $task_options = [];
                            foreach ($data['options'] as $option_key => $option_value) {
                                $task_options[] = [
                                    'key' => $option_key,
                                    'value' => $option_value,
                                ];
                            }
                            $task = $this->taskFactory->createSetEdgeappOptionsTask($data['id'], $task_options);

                            if (Task::STATUS_ERROR === $task->getStatus()) {
                                $response = [
                                    'status' => 'error',
                                    'message' => 'Task creation failed',
                                ];

                                return new JsonResponse($response);
                            }

                            $this->entityManager->persist($task);
                            $this->entityManager->flush();

                            $response = [
                                'status' => 'executing',
                                'message' => 'Task created',
                                'task_id' => $task->getId(),
                            ];
                        }
                    }

                    $data = $response;
                } elseif ('login' == $data['op']) {
                    $response = [
                        'status' => 'error',
                        'message' => 'App not found',
                    ];

                    if ($this->edgeAppsHelper->edgeAppExists($data['id'])) {
                        $apps_list = $this->edgeAppsHelper->getEdgeAppsList();

                        // die(var_dump($apps_list));
                        // $current_options = $apps_list[$data['id']]['options'];

                        // Find the array entry that is the app we are looking for
                        // Entries in the array are like:
                        // ['id' => 'app_id', 'options' => ['key' => 'value']]

                        $edgeapp = null;
                        foreach ($apps_list as $app) {
                            if ($app['id'] == $data['id']) {
                                $edgeapp = $app;
                                break;
                            }
                        }

                        // $current_options = $edgeapp['login'];
                        if (!empty($data['disable'])) {
                            $task = $this->taskFactory->createRemoveEdgeappBasicAuthTask($data['id']);

                            if (Task::STATUS_ERROR === $task->getStatus()) {
                                $response = [
                                    'status' => 'error',
                                    'message' => 'Task creation failed',
                                ];

                                return new JsonResponse($response);
                            }

                            $this->entityManager->persist($task);
                            $this->entityManager->flush();

                            $response = [
                                'status' => 'executing',
                                'message' => 'Task created',
                                'task_id' => $task->getId(),
                            ];
                        } elseif (!empty($data['login']['basic-auth-username']) && !empty($data['login']['basic-auth-password'])) {
                            $task_options = [
                                'username' => $data['login']['basic-auth-username'],
                                'password' => $data['login']['basic-auth-password'],
                            ];

                            $task = $this->taskFactory->createSetEdgeappBasicAuthTask($data['id'], $task_options);

                            if (Task::STATUS_ERROR === $task->getStatus()) {
                                $response = [
                                    'status' => 'error',
                                    'message' => 'Task creation failed',
                                ];

                                return new JsonResponse($response);
                            }

                            $this->entityManager->persist($task);
                            $this->entityManager->flush();

                            $response = [
                                'status' => 'executing',
                                'message' => 'Task created',
                                'task_id' => $task->getId(),
                            ];
                        } else {
                            $response = [
                                'status' => 'error',
                                'message' => 'Invalid options for basic authentication setup',
                            ];
                        }
                    }

                    $data = $response;
                } else {
                    $data = [
                        'status' => 'error',
                        'message' => 'Invalid operation',
                    ];
                }
            } else {
                $data = [
                    'status' => 'error',
                    'message' => 'Invalid operation',
                ];
            }
        } else {
            $data = $this->edgeAppsHelper->getEdgeAppsList();
        }

        return new JsonResponse($data);
    }
}
