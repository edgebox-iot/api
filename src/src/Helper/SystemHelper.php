<?php

namespace App\Helper;

use App\Entity\Option;
use App\Repository\OptionRepository;

class SystemHelper
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

    public function getUptimeInSeconds(): int
    {
        $system_uptime_option = $this->optionRepository->findOneBy(['name' => 'SYSTEM_UPTIME']) ?? new Option();

        if (null === $system_uptime_option->getValue()) {
            return 0;
        }

        $uptime = (int) $system_uptime_option->getValue();

        return $uptime;
    }
}
