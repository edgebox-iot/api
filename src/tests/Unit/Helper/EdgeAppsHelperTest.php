<?php

namespace App\Tests\Unit\Helper;

use App\Entity\Option;
use App\Helper\EdgeAppsHelper;
use App\Helper\EdgeboxioApiConnector;
use App\Helper\SystemHelper;
use App\Repository\OptionRepository;
use PHPUnit\Framework\TestCase;

class EdgeAppsHelperTest extends TestCase
{
    public function testGetEdgeAppsListNoOption(): void
    {
        $mockOptionRepository = $this->getMockBuilder(OptionRepository::class)->disableOriginalConstructor()->getMock();
        $mockOptionRepository->method('findOneBy')->willReturn(null);

        $mockEdgeboxioApiConnector = $this->getMockBuilder(EdgeboxioApiConnector::class)->disableOriginalConstructor()->getMock();
        $edgeAppsHelper = new EdgeAppsHelper($mockOptionRepository, $mockEdgeboxioApiConnector, $this->getMockBuilder(SystemHelper::class)->disableOriginalConstructor()->getMock());

        self::assertEquals([], $edgeAppsHelper->getEdgeAppsList());
    }

    public function testGetEdgeAppsListOption(): void
    {
        $option = new Option();
        $option->setValue(
            json_encode(
                [
                    [
                        'id' => 'asdf',
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
        $edgeAppsHelper = new EdgeAppsHelper($mockOptionRepository, $mockEdgeboxioApiConnector, $this->getMockBuilder(SystemHelper::class)->disableOriginalConstructor()->getMock());

        self::assertCount(1, $edgeAppsHelper->getEdgeAppsList());
    }

    public function testEdgeAppExistsMatch(): void
    {
        $option = new Option();
        $option->setValue(
            json_encode(
                [
                    [
                        'id' => 'asdf',
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
        $edgeAppsHelper = new EdgeAppsHelper($mockOptionRepository, $mockEdgeboxioApiConnector, $this->getMockBuilder(SystemHelper::class)->disableOriginalConstructor()->getMock());

        self::assertTrue($edgeAppsHelper->edgeAppExists('asdf'));
    }

    public function testEdgeAppExistsNonMatch(): void
    {
        $option = new Option();
        $option->setValue(
            json_encode(
                [
                    [
                        'id' => 'asdf',
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
        $edgeAppsHelper = new EdgeAppsHelper($mockOptionRepository, $mockEdgeboxioApiConnector, $this->getMockBuilder(SystemHelper::class)->disableOriginalConstructor()->getMock());

        self::assertFalse($edgeAppsHelper->edgeAppExists('fdsa'));
    }
}
