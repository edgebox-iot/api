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

    public function setOptionValue(string $name, string $value): void
    {
        $option = $this->optionRepository->findOneBy(['name' => $name]) ?? new Option();
        $option->setName($name);
        $option->setValue($value);
        $this->entityManager->persist($option);
        $this->entityManager->flush();
    }

    public function getOptionValue(string $name): ?string
    {
        $option = $this->optionRepository->findOneBy(['name' => $name]) ?? new Option();
        return $option->getValue();
    }

    public function getSystemChangelogVersion(): string
    {
        $changelog_files = glob(__DIR__ . '/../../templates/changelog/*.html.twig');
        $changelog_files = array_map('basename', $changelog_files);
        $changelog_files = array_map(fn($file) => str_replace('.html.twig', '', $file), $changelog_files);
        $changelog_files = array_map(fn($file) => str_replace('changelog-', '', $file), $changelog_files);
        $changelog_files = array_map(fn($file) => str_replace('-', '.', $file), $changelog_files);
        $changelog_files = array_map('floatval', $changelog_files);
        $changelog_files = array_filter($changelog_files, fn($file) => $file > 0);
        $changelog_files = array_unique($changelog_files);
        rsort($changelog_files);
        return $changelog_files[0] ?? '1.2.0';
    }

    public function getSettings(): array
    {
        $color_mood_value = $this->getOptionValue('DASHBOARD_COLOR_MOOD') ?? 'primary';
        $sidebar_style_value = $this->getOptionValue('DASHBOARD_SIDEBAR_STYLE') ?? 'bg-transparent';
        $topbar_style_value = $this->getOptionValue('DASHBOARD_TOPBAR_STYLE') ?? 'float';
        $show_only_installed_apps_value = $this->getOptionValue('DASHBOARD_SHOW_ONLY_INSTALLED_APPS') ?? 'no';
        $show_me_when_it_twerks_value = $this->getOptionValue('DASHBOARD_SHOW_ME_WHEN_IT_TWERKS') ?? 'no';
        $block_default_apps_public_access_value = $this->getOptionValue('DASHBOARD_BLOCK_DEFAULT_APPS_PUBLIC_ACCESS') ?? 'no';
        $show_purpose_label_on_quick_access_icons = $this->getOptionValue('DASHBOARD_SHOW_PURPOSE_LABEL_ON_QUICK_ACCESS_ICONS') ?? 'yes';

        $data = [
            'color_mood' => $color_mood_value,
            'sidebar_style' => $sidebar_style_value,
            'topbar_style' => $topbar_style_value,
            'show_only_installed_apps' => $show_only_installed_apps_value,
            'show_me_when_it_twerks' => $show_me_when_it_twerks_value,
            'block_default_apps_public_access' => $block_default_apps_public_access_value,
            'show_purpose_label_on_quick_access_icons' => $show_purpose_label_on_quick_access_icons,
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

        if (!empty($data['block_default_apps_public_access'])) {
            $this->setOptionValue('DASHBOARD_BLOCK_DEFAULT_APPS_PUBLIC_ACCESS', $data['block_default_apps_public_access']);
        }

        if (!empty($data['show_purpose_label_on_quick_access_icons'])) {
            $this->setOptionValue('DASHBOARD_SHOW_PURPOSE_LABEL_ON_QUICK_ACCESS_ICONS', $data['show_purpose_label_on_quick_access_icons']);
        }

        return $data;
    }
}
