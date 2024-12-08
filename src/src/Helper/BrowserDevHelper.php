<?php

namespace App\Helper;

use App\Entity\Option;
use App\Factory\TaskFactory;
use App\Repository\OptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class BrowserDevHelper
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

    public function disableBrowserDev(): array
    {
        $task = $this->taskFactory->createDisableBrowserDevTask();
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return [
            'task_id' => $task->getId(),
            'task_status' => $task->getStatus(),
            'task_args' => $task->getArgs(),
        ];
    }

    public function enableBrowserDev(): array
    {
        $task = $this->taskFactory->createEnableBrowserDevTask();
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return [
            'task_id' => $task->getId(),
            'task_status' => $task->getStatus(),
            'task_args' => $task->getArgs(),
        ];
    }

    public function setBrowserDevPassword(string $password): array
    {
        $task = $this->taskFactory->createSetBrowserDevPasswordTask($password);
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return [
            'task_id' => $task->getId(),
            'task_status' => $task->getStatus(),
            'task_args' => $task->getArgs(),
        ];
    }

    public function getBrowserDevPassword(): string
    {
        return $this->optionRepository->findBrowserDevPassword() ?? '';
    }

    public function getBrowserDevUrl(): string
    {
        return 'https://dev.'.$this->optionRepository->findDomainName();
    }

    public function getBrowserDevStatus($with_password = false): array
    {
        $running_result = [
            'status' => 'not_running',
            'status_message' => 'Browser Dev Environment is not running',
            'url' => $this->getBrowserDevUrl(),
        ];
        if ($with_password) {
            $password = $this->getBrowserdevPassword();
            $running_result['password'] = $password;
        }

        $browser_dev_status_option = $this->optionRepository->findBrowserDevStatus() ?? new Option();

        if (null === $browser_dev_status_option || 'null' === $browser_dev_status_option || 'not_running' === $browser_dev_status_option) {
            return $running_result;
        }

        $val = $browser_dev_status_option;

        if ('running' === $val) {
            $running_result = [
                'status' => 'running',
                'status_message' => 'Browser Dev Environment is running',
                'url' => $this->getBrowserDevUrl(),
            ];
            if ($with_password) {
                $password = $this->getBrowserdevPassword();
                $running_result['password'] = $password;
            }
        }

        return $running_result;
    }
}
