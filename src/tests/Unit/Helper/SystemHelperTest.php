<?php

namespace Tests\App\Unit\Helper;

use App\Entity\Option;
use App\Helper\SystemHelper;
use App\Repository\OptionRepository;
use PHPUnit\Framework\TestCase;

class SystemHelperTest extends TestCase
{
    public function testGetUptimeInSecondsNoOption(): void
    {
        $mockOptionRepository = $this->getMockBuilder(OptionRepository::class)->disableOriginalConstructor()->getMock();
        $mockOptionRepository->method('findOneBy')->willReturn(null);
        $systemHelper = new SystemHelper($mockOptionRepository);

        self::assertEquals(0, $systemHelper->getUptimeInSeconds());
    }

    public function testGetUptimeInSecondsOption(): void
    {
        $option = new Option();
        $option->setValue('500');
        $mockOptionRepository = $this->getMockBuilder(OptionRepository::class)->disableOriginalConstructor()->getMock();
        $mockOptionRepository->method('findOneBy')->willReturn($option);
        $systemHelper = new SystemHelper($mockOptionRepository);

        self::assertEquals(500, $systemHelper->getUptimeInSeconds());
    }
}
