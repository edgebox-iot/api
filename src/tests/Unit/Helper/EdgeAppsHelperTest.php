<?php

namespace App\Tests\Unit\Helper;

use App\Helper\EdgeAppsHelper;
use App\Helper\EdgeboxioApiConnector;
use App\Repository\OptionRepository;
use PHPUnit\Framework\TestCase;

class EdgeAppsHelperTest extends TestCase
{
    public function testGetEdgeAppsListNoOption(): void
    {
        $mockOptionRepository = $this->getMockBuilder(OptionRepository::class)->disableOriginalConstructor()->getMock();
        $mockOptionRepository->method('findOneBy')->willReturn(null);

        $mockEdgeboxioApiConnector = $this->getMockBuilder(EdgeboxioApiConnector::class)->disableOriginalConstructor()->getMock();
        $edgeAppsHelper = new EdgeAppsHelper($mockOptionRepository, $mockEdgeboxioApiConnector);

        self::assertEquals([], $edgeAppsHelper->getEdgeAppsList());
    }
}
