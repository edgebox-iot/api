<?php

namespace App\Helper;

use App\Entity\Option;
use App\Repository\OptionRepository;
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

    public static function getOverallStorageSummary(array $storageDevicesList): array
    {
        $result = [
            'percentage' => '',
            'free' => '',
        ];

        $total_storage = 0.0;
        $total_storage_used = 0.0;
        $total_storage_free = 0.0;

        foreach ($storageDevicesList as $device) {
            $total_storage += $device['usage_stat']['total'];
            $total_storage_used += $device['usage_stat']['used'];
            $total_storage_free += $device['usage_stat']['free'];
        }

        $percentage_used = (($total_storage_used / $total_storage) * 100);

        $result['percentage'] = round($percentage_used, 0).'%';
        $result['free'] = StorageHelper::humanizeBytesValue($total_storage_free, 0).' free';

        return $result;
    }

    public static function humanizeDeviceUsageValues(array $storageDevicesList, bool $include_partitions = false): array
    {
        foreach ($storageDevicesList as $deviceKey => $deviceInfo) {
            if ($deviceInfo['in_use']) {
                // Humanize the total device storage size
                $storageDevicesList[$deviceKey]['size'] = self::humanizeBytesValue($deviceInfo['size']);

                // First Humanize Partition data split to percentages of total space
                $storageDevicesList[$deviceKey]['usage_stat']['usage_split']['os'] = (($deviceInfo['usage_stat']['usage_split']['os'] / $deviceInfo['usage_stat']['total']) * 100);
                $storageDevicesList[$deviceKey]['usage_stat']['usage_split']['edgeapps'] = (($deviceInfo['usage_stat']['usage_split']['edgeapps'] / $deviceInfo['usage_stat']['total']) * 100);
                $storageDevicesList[$deviceKey]['usage_stat']['usage_split']['buckets'] = (($deviceInfo['usage_stat']['usage_split']['buckets'] / $deviceInfo['usage_stat']['total']) * 100);
                $storageDevicesList[$deviceKey]['usage_stat']['usage_split']['others'] = (($deviceInfo['usage_stat']['usage_split']['others'] / $deviceInfo['usage_stat']['total']) * 100);

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

    public static function humanizeBytesValue(float $bytes, int $decimals = 2): string
    {
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).@$size[$factor];
    }
}
