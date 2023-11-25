<?php

namespace App\Helper;

use App\Entity\Option;
use App\Factory\TaskFactory;
use App\Repository\OptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class ShellHelper
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

    private function setOptionValue(string $name, string $value): void
    {
        $option = $this->optionRepository->findOneBy(['name' => $name]) ?? new Option();
        $option->setName($name);
        $option->setValue($value);
        $this->entityManager->persist($option);
        $this->entityManager->flush();
    }

    public function stopShell(): array
    {
        $task = $this->taskFactory->createStopShellTask();
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return [
            'task_id' => $task->getId(),
            'task_status' => $task->getStatus(),
            'task_args' => $task->getArgs(),
        ];
    }

    public function startShell(int $timeout): array
    {
        $task = $this->taskFactory->createStartShellTask($timeout);
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return [
            'task_id' => $task->getId(),
            'task_status' => $task->getStatus(),
            'task_args' => $task->getArgs(),
        ];
    }

    public function getShellStatus(): array
    {
        $shell_status_option = $this->optionRepository->findOneBy(['name' => 'SHELL_STATUS']) ?? new Option();

        if (null === $shell_status_option->getValue() || 'null' === $shell_status_option->getValue() || 'not_running' === $shell_status_option->getValue()) {
            return [
                'status' => 'not_running',
                'status_message' => 'Shell is not running',
            ];
        }

        $val = $shell_status_option->getValue();

        if ('running' === $val) {
            return [
                'status' => 'running',
                'status_message' => 'Shell is running',
                'url' => $this->optionRepository->findOneBy(['name' => 'SHELL_URL'])->getValue(),
            ];
        } else {
            return [
                'status' => 'error',
                'status_message' => 'Shell is in an unknown state',
                'value' => $val,
            ];
        }
    }
}
