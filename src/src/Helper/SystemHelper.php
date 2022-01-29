<?php

namespace App\Helper;

use App\Entity\Option;
use App\Repository\OptionRepository;

class SystemHelper
{
    public const VERSION_DEV = 'dev';
    public const VERSION_PROD = 'prod';
    public const VERSION_CLOUD = 'cloud';

    private OptionRepository $optionRepository;

    public function __construct(
        OptionRepository $optionRepository
    ) {
        $this->optionRepository = $optionRepository;
    }

    public function getUptimeInSeconds(): int
    {
        $system_uptime_option = $this->optionRepository->findOneBy(['name' => 'SYSTEM_UPTIME']) ?? new Option();

        if (null === $system_uptime_option->getValue()) {
            return 0;
        }

        $uptime = (int) $system_uptime_option->getValue();

        return $uptime;
    }

    public function getReleaseVersion(): ?string
    {
        $release_version_option = $this->optionRepository->findOneBy(['name' => 'RELEASE_VERSION']) ?? new Option();

        if (null === $release_version_option->getValue()) {
            return null;
        }

        return $release_version_option->getValue();
    }

    public function isOnlineReady(): bool
    {
        // Checking if a domain name is configured in the system, either manual via domain name setting, or via the myedge.app service login.
        // Can use 1 or the other. Domain name setting takes precendence.

        // First check. Is there a manual domain name set?
        $domain_option = $this->optionRepository->findOneBy(['name' => 'DOMAIN_NAME']);

        if (null != $domain_option && !empty($domain_option->getValue())) {
            return true;
        }

        // Second check. Is there a previous login token saved for edgebox.io API?
        $token_option = $this->optionRepository->findOneBy(['name' => 'EDGEBOXIO_API_TOKEN']);
        if (null != $token_option) {
            return true;
        }

        return false;
    }

    public function getIP(): string
    {
        $ip = '';
        $ip_option = $this->optionRepository->findOneBy(['name' => 'IP_ADDRESS']);

        if (null != $ip_option && !empty($ip_option->getValue())) {
            $ip = $ip_option->getValue();
        }

        return $ip;
    }

    public function isDashboardPublic(): bool
    {
        $result = false;

        $public_dashboard_otpion = $this->optionRepository->findOneBy(['name' => 'PUBLIC_DASHBOARD']);

        if (null != $public_dashboard_otpion && !empty($public_dashboard_otpion->getValue())) {
            $result = true;
        }

        return $result;
    }
}
