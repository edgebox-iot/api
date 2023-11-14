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

        $show_only_installed_apps_option = $this->optionRepository->findOneBy(['name' => 'DASHBOARD_SHOW_ONLY_INSTALLED_APPS']) ?? new Option();
        $show_only_installed_apps_value = $show_only_installed_apps_option->getValue();
        if (!$show_only_installed_apps_value) {
            $show_only_installed_apps_value = false;
        }

        $show_me_when_it_twerks_option = $this->optionRepository->findOneBy(['name' => 'DASHBOARD_SHOW_ME_WHEN_IT_TWERKS']) ?? new Option();
        $show_me_when_it_twerks_value = $show_me_when_it_twerks_option->getValue();
        if (!$show_me_when_it_twerks_value) {
            $show_me_when_it_twerks_value = 'no';
        }

        $data = [
            'color_mood' => $color_mood_value,
            'sidebar_style' => $sidebar_style_value,
            'topbar_style' => $topbar_style_value,
            'show_only_installed_apps' => $show_only_installed_apps_value,
            'show_me_when_it_twerks' => $show_me_when_it_twerks_value
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

        if (!empty($data['show_only_installed_apps'])) {
            $this->setOptionValue('DASHBOARD_SHOW_ONLY_INSTALLED_APPS', $data['show_only_installed_apps']);
        }

        if (!empty($data['show_me_when_it_twerks'])) {
            $this->setOptionValue('DASHBOARD_SHOW_ME_WHEN_IT_TWERKS', $data['show_me_when_it_twerks']);
        }

        return $data;
    }
}
