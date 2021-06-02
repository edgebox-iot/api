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

    public function getInternetUrl(string $app_id): ?string {

        $token_option = $this->optionRepository->findOneBy(['name' => 'EDGEBOXIO_API_TOKEN']) ?? new Option();

        if (null === $token_option->getValue()) {
            return null;
        }

        $edgeboxio_api = new EdgeboxioApiConnector();
        $url_registration_response = $edgeboxio_api->register_apps($token_option->getValue(), $app_id);

        if (!empty($url_registration_response['status']) && $url_registration_response['status'] == 'success') {


            $app_info = !empty($url_registration_response['value']['apps'][$app_id]) ? $url_registration_response['value']['apps'][$app_id] : [];

            print_r($url_registration_response['value']['apps']);

            // Check if registration was successfull and only then issue the appliance to set configurations.
            if (!empty($app_info) && !empty($app_info['url'])) {
                return $app_info['url'];
            } else {
                return null;
            }
        }

    }
}
