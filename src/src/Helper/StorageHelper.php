<?php

namespace App\Helper;

use App\Entity\Option;
use App\Repository\OptionRepository;

use Coduo\PHPHumanizer\NumberHumanizer;
use Coduo\PHPHumanizer\String\Humanize;

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

    static function humanizeDeviceUsageValues(array $storageDevicesList, bool $include_partitions = false): array {

        foreach ($storageDevicesList as $deviceKey => $deviceInfo) {

            if($deviceInfo['in_use']) {

                // Humanize the total device storage size
                $storageDevicesList[$deviceKey]['size'] = self::humanizeBytesValue($deviceInfo['size']);

                // First Humanize Partition data split to percentages of total space
                $storageDevicesList[$deviceKey]['usage_stat']['usage_split']['os'] = (int)(($deviceInfo['usage_stat']['usage_split']['os'] / $deviceInfo['usage_stat']['total']) * 100);
                $storageDevicesList[$deviceKey]['usage_stat']['usage_split']['edgeapps'] = (int)(($deviceInfo['usage_stat']['usage_split']['edgeapps'] / $deviceInfo['usage_stat']['total']) * 100);
                $storageDevicesList[$deviceKey]['usage_stat']['usage_split']['buckets'] = (int)(($deviceInfo['usage_stat']['usage_split']['buckets'] / $deviceInfo['usage_stat']['total']) * 100);
                $storageDevicesList[$deviceKey]['usage_stat']['usage_split']['others'] = (int)(($deviceInfo['usage_stat']['usage_split']['others'] / $deviceInfo['usage_stat']['total']) * 100);

                // Then Humanize Partition storage usage values, won't be needed anymore as bytes
                $storageDevicesList[$deviceKey]['usage_stat']['total'] = self::humanizeBytesValue($deviceInfo['usage_stat']['total'], 2);
                $storageDevicesList[$deviceKey]['usage_stat']['used'] = self::humanizeBytesValue($deviceInfo['usage_stat']['used'], 2);
                $storageDevicesList[$deviceKey]['usage_stat']['free'] = self::humanizeBytesValue($deviceInfo['usage_stat']['free'], 2);

            } else {
                $storageDevicesList[$deviceKey]['size'] = self::humanizeBytesValue($deviceInfo['size']);
            }

        }

        return $storageDevicesList;
    }

    static function humanizeBytesValue($bytes, $decimals = 2) {
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

}
