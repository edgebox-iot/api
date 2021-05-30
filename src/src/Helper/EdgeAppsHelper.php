<?php

namespace App\Helper;

use App\Entity\Option;
use App\Repository\OptionRepository;

class EdgeAppsHelper
{
    /**
     * @var OptionRepository
     */
    private $optionRepository;

    public function __construct(
        OptionRepository $optionRepository
    ) {
        $this->optionRepository = $optionRepository;
    }

    public function getEdgeAppsList(): array
    {
        $apps_list_option = $this->optionRepository->findOneBy(['name' => 'EDGEAPPS_LIST']) ?? new Option();

        if (null === $apps_list_option->getValue()) {
            return [];
        }

        return json_decode($apps_list_option->getValue(), true);
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
}
