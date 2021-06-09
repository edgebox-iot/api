<?php

namespace App\Tests\Unit\Helper;

use App\Entity\Option;
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

    public function testGetEdgeAppsListOption(): void
    {
        $option = new Option();
        $option->setValue(
            json_encode(
                [
                    [
                        'id' => 1,
                        'status' => [
                            'description' => 'on',
                        ],
                        'description' => 'Something awesome',
                        'name' => 'Something',
                        'internet_accessible' => true,
                        'network_url' => 'https://example.com',
                    ],
                ],
                JSON_THROW_ON_ERROR
            )
        );
        $mockOptionRepository = $this->getMockBuilder(OptionRepository::class)->disableOriginalConstructor()->getMock();
        $mockOptionRepository->method('findOneBy')->willReturn($option);

        $mockEdgeboxioApiConnector = $this->getMockBuilder(EdgeboxioApiConnector::class)->disableOriginalConstructor()->getMock();
        $edgeAppsHelper = new EdgeAppsHelper($mockOptionRepository, $mockEdgeboxioApiConnector);

        self::assertCount(1, $edgeAppsHelper->getEdgeAppsList());
    }
}
