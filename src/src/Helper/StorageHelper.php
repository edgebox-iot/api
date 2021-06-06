<?php

namespace App\Helper;

use App\Entity\Option;
use App\Repository\OptionRepository;

class StorageHelper
{
    private OptionRepository $optionRepository;

    public function __construct(
        OptionRepository $optionRepository
    ) {
        $this->optionRepository = $optionRepository;
    }

    public function getStorageDevicesList(): array
    {
        $storage_devices_list_option = $this->optionRepository->findOneBy(['name' => 'STORAGE_DEVICES_LIST']) ?? new Option();

        if (null === $storage_devices_list_option->getValue() || 'null' === $storage_devices_list_option->getValue()) {
            return [];
        }

        return json_decode($storage_devices_list_option->getValue(), true);
    }
}
