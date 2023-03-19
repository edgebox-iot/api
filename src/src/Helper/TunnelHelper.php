<?php

namespace App\Helper;

use App\Entity\Option;
use App\Entity\Task;
use App\Factory\TaskFactory;
use App\Repository\OptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class TunnelHelper
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

    public function configureTunnel(string $domain_name): array
    {
        // This method issues a task so edgeboxctl can start the tunnel configuration
        $task = $this->taskFactory->createSetupTunnelTask($domain_name);
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return [
            'task_id' => $task->getId(),
            'task_status' => $task->getStatus(),
            'task_args' => $task->getArgs(),
        ];
    }

    public function stopTunnel(): array
    {
        $task = $this->taskFactory->createStopTunnelTask();
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return [
            'task_id' => $task->getId(),
            'task_status' => $task->getStatus(),
            'task_args' => $task->getArgs(),
        ];
    }

    public function startTunnel(): array
    {
        $task = $this->taskFactory->createStartTunnelTask();
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return [
            'task_id' => $task->getId(),
            'task_status' => $task->getStatus(),
            'task_args' => $task->getArgs(),
        ];
    }

    public function disableTunnel(): array
    {
        $task = $this->taskFactory->createDisableTunnelTask();
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return [
            'task_id' => $task->getId(),
            'task_status' => $task->getStatus(),
            'task_args' => $task->getArgs(),
        ];
    }

    public function getTunnelStatus(): array
    {
        $tunnel_status_option = $this->optionRepository->findOneBy(['name' => 'TUNNEL_STATUS']) ?? new Option();

        if (null === $tunnel_status_option->getValue() || 'null' === $tunnel_status_option->getValue()) {
            return [
                'status' => 'not_configured',
                'message' => 'Tunnel is not configured',
            ];
        }

        $val = $tunnel_status_option->getValue();

        return json_decode($val, true);
    }
}
