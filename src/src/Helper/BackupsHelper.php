<?php

namespace App\Helper;

use App\Entity\Option;
use App\Entity\Task;
use App\Factory\TaskFactory;
use App\Repository\OptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class BackupsHelper
{
    private OptionRepository $optionRepository;
    private EntityManagerInterface $entityManager;
    private TaskFactory $taskFactory;

    public const SUPPORTED_SERVICES = [
        'b2',
        's3',
        'wasabi',
    ];

    public function __construct(
        OptionRepository $optionRepository,
        EntityManagerInterface $entityManager,
        TaskFactory $taskFactory
    ) {
        $this->optionRepository = $optionRepository;
        $this->entityManager = $entityManager;
        $this->taskFactory = $taskFactory;
    }

    private function setOptionValue(string $name, string $value): void
    {
        $option = $this->optionRepository->findOneBy(['name' => $name]) ?? new Option();
        $option->setName($name);
        $option->setValue($value);
        $this->entityManager->persist($option);
        $this->entityManager->flush();
    }

    public function getLastBackupRunTime(): string
    {
        $backups_last_run_option = $this->optionRepository->findOneBy(['name' => 'BACKUP_LAST_RUN']) ?? new Option();
        if (null === $backups_last_run_option->getValue() || 'null' === $backups_last_run_option->getValue()) {
            return 'Never';
        } else {
            $last_run = $backups_last_run_option->getValue();
        }

        $total_seconds = time() - (int) $last_run;

        $days = $total_seconds / (60 * 60 * 24);
        $hours = $total_seconds / (60 * 60);
        $minutes = $total_seconds / 60;

        if ($days >= 2) {
            return (int) $days.' days ago';
        }

        if ($hours >= 2) {
            return (int) $hours.'h ago';
        }

        if ($minutes >= 1) {
            return (int) $minutes.'m ago';
        }

        return 'Just now';
    }

    public function validateArgs(array $args): bool
    {
        if (!isset($args['service']) || !in_array($args['service'], self::SUPPORTED_SERVICES)) {
            return false;
        }

        if (!isset($args['access_key_id'])) {
            return false;
        }

        if (!isset($args['secret_access_key'])) {
            return false;
        }

        if (!isset($args['repository_name'])) {
            return false;
        }

        if (!isset($args['repository_password'])) {
            return false;
        }

        return true;
    }

    public function configureBackups(string $service, string $access_key_id, string $secret_access_key, string $repository_name, string $repository_password): array
    {
        // This method issues a task so edgeboxctl can start the backups configuration
        $task = $this->taskFactory->createSetupBackupsTask($service, $access_key_id, $secret_access_key, $repository_name, $repository_password);
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return [
            'task_id' => $task->getId(),
            'task_status' => $task->getStatus(),
            'task_args' => $task->getArgs(),
        ];
    }

    // public function stopTunnel(): array
    // {
    //     $task = $this->taskFactory->createStopTunnelTask();
    //     $this->entityManager->persist($task);
    //     $this->entityManager->flush();

    //     return [
    //         'task_id' => $task->getId(),
    //         'task_status' => $task->getStatus(),
    //         'task_args' => $task->getArgs(),
    //     ];
    // }

    public function startBackup(): array
    {
        $task = $this->taskFactory->createStartBackupTask();
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return [
            'task_id' => $task->getId(),
            'task_status' => $task->getStatus(),
            'task_args' => $task->getArgs(),
        ];
    }

    public function stopBackup(): array
    {
        return [];
    }

    public function restoreBackups(): array
    {
        $task = $this->taskFactory->createRestoreBackupsTask();
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return [
            'task_id' => $task->getId(),
            'task_status' => $task->getStatus(),
            'task_args' => $task->getArgs(),
        ];
    }

    public function disableBackups(): array
    {
        $task = $this->taskFactory->createDisableBackupsTask();
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return [
            'task_id' => $task->getId(),
            'task_status' => $task->getStatus(),
            'task_args' => $task->getArgs(),
        ];
    }

    public function removeBackupsConfig(): array
    {
        $this->setOptionValue('BACKUP_STATUS', '');

        return [
            'status' => 'not_configured',
            'message' => 'Backups are not configured',
        ];
    }

    public function getBackupsStatus(): array
    {
        $backups_status_option = $this->optionRepository->findOneBy(['name' => 'BACKUP_STATUS']) ?? new Option();

        if (null === $backups_status_option->getValue() || 'null' === $backups_status_option->getValue()) {
            return [
                'status' => 'not_configured',
                'message' => 'Backups are not configured',
            ];
        }

        $val = $backups_status_option->getValue();

        if ('' === $val) {
            return [
                'status' => 'not_configured',
                'message' => 'Backups are not configured',
            ];
        } elseif ('initiated' === $val) {
            return [
                'status' => 'initiated',
                'message' => 'Repository is initiated and waiting for the first manual backup!',
            ];
        } elseif ('working' === $val) {
            return [
                'status' => 'working',
                'message' => 'Backups are working',
                'stats' => $this->getBackupStats(),
                'is_running' => $this->isBackupsRunning(),
                'last_run' => $this->getLastBackupRunTime(),
            ];
        } elseif ('disabled' === $val) {
            return [
                'status' => 'disabled',
                'message' => 'Backups are disabled',
            ];
        } elseif ('error' === $val) {
            // An caught error was found, fetch the message
            $backups_status_error = $this->optionRepository->findOneBy(['name' => 'BACKUP_ERROR_MESSAGE']) ?? new Option();

            return [
                'status' => 'error',
                'message' => $backups_status_error->getValue(),
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'An error occured with the backups system',
            ];
        }

        return json_decode($val, true);
    }

    public function getBackupStats(): array
    {
        $backups_stats_option = $this->optionRepository->findOneBy(['name' => 'BACKUP_STATS']) ?? new Option();

        if (null === $backups_stats_option->getValue() || 'null' === $backups_stats_option->getValue()) {
            return [
                'processed_snapshots' => 0,
                'total_file_count' => 0,
                'total_size' => '0 B',
            ];
        }

        $logString = $backups_stats_option->getValue();

        // Extract the information using regular expressions
        preg_match('/Snapshots processed:\s+(\d+)/', $logString, $matches);
        $snapshotsProcessed = isset($matches[1]) ? $matches[1] : null;

        preg_match('/Total File Count:\s+(\d+)/', $logString, $matches);
        $totalFileCount = isset($matches[1]) ? $matches[1] : null;

        preg_match('/Total Size:\s+([\d.]+)\s+(\w+)/', $logString, $matches);
        $totalSize = isset($matches[1]) ? $matches[1] : null;
        $sizeUnit = isset($matches[2]) ? $matches[2] : null;

        $parsed_stats = [
            'processed_snapshots' => $snapshotsProcessed,
            'total_file_count' => $totalFileCount,
            'total_size' => $totalSize.' '.$sizeUnit,
        ];

        return $parsed_stats;
    }

    public function isBackupsRunning(): bool
    {
        $backups_status_option = $this->optionRepository->findOneBy(['name' => 'BACKUP_IS_WORKING']) ?? new Option();

        if (null === $backups_status_option->getValue() || 'null' === $backups_status_option->getValue()) {
            return false;
        }

        $val = $backups_status_option->getValue();

        if ('1' == $val) {
            return true;
        }

        return false;
    }
}
