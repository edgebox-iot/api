<?php

namespace App\Controller;

use App\Helper\DashboardHelper;
use App\Repository\OptionRepository;
use App\Entity\Option;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// Use in controllers:
abstract class BaseController extends AbstractController 
{

    protected DashboardHelper $dashboardHelper;

    public function __construct(DashboardHelper $dashboardHelper) 
    {
        $this->dashboardHelper = $dashboardHelper;
    }

    public function checkChangelogRedirect(): ?Response
    {
        $current_system_changelog_version = $this->dashboardHelper->getSystemChangelogVersion();

        $last_seen_changelog_version = $this->dashboardHelper->getOptionValue('LAST_SEEN_CHANGELOG_VERSION') ?? '0';
        
        if ($current_system_changelog_version != $last_seen_changelog_version) {
            $this->dashboardHelper->setOptionValue('LAST_SEEN_CHANGELOG_VERSION', $current_system_changelog_version);
            return $this->redirectToRoute('changelog-version', ['version' => $current_system_changelog_version]);
        }

        return null;
    }

    public function checkOnboardingRedirect(): ?Response
    {
        $onboarding_completed = $this->dashboardHelper->getOptionValue('ONBOARDING_COMPLETED') ?? 'no';

        if ($onboarding_completed == 'no') {
            $onboarding_completed = 'yes';
            $this->dashboardHelper->setOptionValue('ONBOARDING_COMPLETED', $onboarding_completed);
            return $this->redirectToRoute('hello');
        }

        return null;
    }
}