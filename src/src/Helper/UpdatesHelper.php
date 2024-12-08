<?php

namespace App\Helper;

use App\Factory\TaskFactory;
use App\Repository\OptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class UpdatesHelper
{
    private OptionRepository $optionRepository;
    private EntityManagerInterface $entityManager;
    private TaskFactory $taskFactory;

    public function __construct(
        OptionRepository $optionRepository,
        EntityManagerInterface $entityManager,
        TaskFactory $taskFactory
    ) {
        $this->optionRepository = $optionRepository;
        $this->entityManager = $entityManager;
        $this->taskFactory = $taskFactory;
    }

    public function checkUpdates(): array
    {
        $task = $this->taskFactory->createCheckUpdatesTask();
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return [
            'task_id' => $task->getId(),
            'task_status' => $task->getStatus(),
            'task_args' => $task->getArgs(),
        ];
    }

    public function applyUpdates(): array
    {
        $task = $this->taskFactory->createApplyUpdatesTask();
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return [
            'task_id' => $task->getId(),
            'task_status' => $task->getStatus(),
            'task_args' => $task->getArgs(),
        ];
    }

    public function getUpdatesStatus(): array
    {
        $updates_status_option = $this->optionRepository->findUpdatesStatus();

        if (null === $updates_status_option || 'null' === $updates_status_option || '[]' === $updates_status_option) {
            return [
                'status' => 'up_to_date',
                'status_message' => 'No updates available',
            ];
        }

        $val = $updates_status_option;

        // Convert to JSON
        $val = json_decode($val, true);

        return [
            'status' => 'updates_available',
            'status_message' => 'Updates can be applied',
            'value' => $val,
        ];
    }
}
