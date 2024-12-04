<?php

namespace App\Controller;

use App\Entity\Task;
use App\Factory\TaskFactory;
use App\Helper\BackupsHelper;
use App\Helper\DashboardHelper;
use App\Helper\EdgeAppsHelper;
use App\Helper\ShellHelper;
use App\Helper\BrowserDevHelper;
use App\Helper\TunnelHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ApiController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private DashboardHelper $dashboardHelper;
    private TunnelHelper $tunnelHelper;
    private ShellHelper $shellHelper;
    private BrowserDevHelper $browserDevHelper;
    private BackupsHelper $backupsHelper;
    private EdgeAppsHelper $edgeAppsHelper;
    private TaskFactory $taskFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        DashboardHelper $dashboardHelper,
        TunnelHelper $tunnelHelper,
        ShellHelper $shellHelper,
        BrowserDevHelper $browserDevHelper,
        BackupsHelper $backupsHelper,
        EdgeAppsHelper $edgeAppsHelper,
        TaskFactory $taskFactory
    ) {
        $this->entityManager = $entityManager;
        $this->dashboardHelper = $dashboardHelper;
        $this->tunnelHelper = $tunnelHelper;
        $this->shellHelper = $shellHelper;
        $this->browserDevHelper = $browserDevHelper;
        $this->backupsHelper = $backupsHelper;
        $this->edgeAppsHelper = $edgeAppsHelper;
        $this->taskFactory = $taskFactory;
    }

    #[Route('/api', name: 'api')]
    public function index(): JsonResponse
    {
        $data = [
            'status' => 'ok',
        ];

        return new JsonResponse($data);
    }

    #[Route('/api/settings/dashboard', name: 'api_settings_dashboard')]
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

    #[Route('/api/settings/tunnel', name: 'api_settings_tunnel')]
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

    #[Route('/api/settings/shell', name: 'api_settings_shell')]
    public function settingsShell(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            $data = json_decode($request->getContent(), true);
            $response = [
                'status' => 'error',
                'message' => 'Invalid operation',
            ];

            if (isset($data['op'])) {
                if ('start' == $data['op']) {
                    $response = $this->shellHelper->startShell($data['timeout']);
                } elseif ('stop' == $data['op']) {
                    $response = $this->shellHelper->stopShell();
                }
            }
        } else {
            $response = $this->shellHelper->getShellStatus();
        }

        return new JsonResponse($response);
    }

    #[Route('/api/settings/browserdev', name: 'api_settings_browserdev')]
    public function settingsBrowserDev(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            $data = json_decode($request->getContent(), true);
            $response = [
                'status' => 'error',
                'message' => 'Invalid operation',
            ];

            if (isset($data['op'])) {
                if ('changepw' == $data['op'] && isset($data['password'])) {
                    $response = $this->browserDevHelper->setBrowserDevPassword($data['password']);
                } elseif ('disable' == $data['op']) {
                    $response = $this->browserDevHelper->disableBrowserDev();
                } elseif ('enable' == $data['op']) {
                    $response = $this->browserDevHelper->enableBrowserDev();
                } elseif ('status' == $data['op']) {
                    $response = $this->browserDevHelper->getBrowserDevStatus();
                }
            }
        } else {
            $response = $this->browserDevHelper->getBrowserDevStatus(true);
        }

        return new JsonResponse($response);
    }

    #[Route('/api/backups', name: 'api_backups')]
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

    #[Route('/api/tasks', name: 'api_tasks')]
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

    #[Route('/api/tasks/{id}', name: 'api_tasks_id')]
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

    #[Route('/api/edgeapps', name: 'api_edgeapps')]
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

                } elseif ('install_bulk_edgeapps' == $data['op']) {
                    # Create a task to install all apps in the list
                    # But remove apps that are alredy installed

                    $apps_list = $this->edgeAppsHelper->getEdgeAppsList();
                    $installed_apps = [];
                    foreach ($apps_list as $app) {
                        if ($app['status']['description'] == 'on') {
                            $installed_apps[] = $app['id'];
                        }
                    }

                    // $data['ids'] is an array like ['id1', 'id2', ...]
                    // It should always have this format

                    $data['ids'] = array_diff($data['ids'], $installed_apps);

                    if (empty($data['ids'])) {
                        $response = [
                            'status' => 'error',
                            'message' => 'No apps to install',
                        ];

                        return new JsonResponse($response);
                    }

                    $task = $this->taskFactory->createInstallBulkEdgeappsTask($data['ids']);
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
