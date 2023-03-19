<?php

namespace App\Helper;

use App\Entity\Option;
use App\Repository\OptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class DashboardHelper
{
    private OptionRepository $optionRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        OptionRepository $optionRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->optionRepository = $optionRepository;
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

    public function getSettings(): array
    {
        $color_mood_option = $this->optionRepository->findOneBy(['name' => 'DASHBOARD_COLOR_MOOD']) ?? new Option();
        $color_mood_value = $color_mood_option->getValue();
        if (!$color_mood_value) {
            $color_mood_value = 'primary';
        }

        $sidebar_style_option = $this->optionRepository->findOneBy(['name' => 'DASHBOARD_SIDEBAR_STYLE']) ?? new Option();
        $sidebar_style_value = $sidebar_style_option->getValue();
        if (!$sidebar_style_value) {
            $sidebar_style_value = 'bg-transparent';
        }

        $topbar_style_option = $this->optionRepository->findOneBy(['name' => 'DASHBOARD_TOPBAR_STYLE']) ?? new Option();
        $topbar_style_value = $topbar_style_option->getValue();
        if (!$topbar_style_value) {
            $topbar_style_value = 'float';
        }

        $data = [
            'color_mood' => $color_mood_value,
            'sidebar_style' => $sidebar_style_value,
            'topbar_style' => $topbar_style_value
        ];

        return $data;
    }

    public function setSettings(array $data): array
    {
        if (!empty($data['color_mood'])) {
            $this->setOptionValue('DASHBOARD_COLOR_MOOD', $data['color_mood']);
        }

        if (!empty($data['sidebar_style'])) {
            $this->setOptionValue('DASHBOARD_SIDEBAR_STYLE', $data['sidebar_style']);
        }

        if (!empty($data['topbar_style'])) {
            $this->setOptionValue('DASHBOARD_TOPBAR_STYLE', $data['topbar_style']);
        }

        return $data;
    }
}
