<?php

namespace App\Helper;

use App\Entity\Option;
use App\Repository\OptionRepository;

class EdgeAppsHelper
{
    private OptionRepository $optionRepository;
    private EdgeboxioApiConnector $edgeboxioApiConnector;

    private SystemHelper $systemHelper;

    public function __construct(
        OptionRepository $optionRepository,
        EdgeboxioApiConnector $edgeboxioApiConnector,
        SystemHelper $systemHelper,
    ) {
        $this->optionRepository = $optionRepository;
        $this->edgeboxioApiConnector = $edgeboxioApiConnector;
        $this->systemHelper = $systemHelper;
    }

    public function getEdgeAppsList(): array
    {
        $apps_list_option = $this->optionRepository->findOneBy(['name' => 'EDGEAPPS_LIST']) ?? new Option();

        if (null === $apps_list_option->getValue() || 'null' === $apps_list_option->getValue()) {
            return [];
        }

        $apps = json_decode($apps_list_option->getValue(), true);

        return $apps;
    }

    public function edgeAppExists(string $app_id): bool
    {
        $found = false;
        $apps_list = $this->getEdgeAppsList();
        if (!empty($apps_list)) {
            foreach ($apps_list as $edge_app) {
                if ($edge_app['id'] == $app_id) {
                    $found = true;

                    return $found;
                }
            }
        }

        return $found;
    }

    public function getInternetUrl(string $appId): ?string
    {
        $domainName = $this->optionRepository->findDomainName();
        if (null !== $domainName) {
            return sprintf('%s.%s', $appId, $domainName);
        }

        if ($this->systemHelper->isCloud()) {
            $cluster = $this->optionRepository->findCluster();
            $host = $this->optionRepository->findUsername();

            return sprintf('%s-%s.%s', $host, $appId, $cluster);
        }

        return null;
    }
}
