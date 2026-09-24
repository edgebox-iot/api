<?php

namespace App\Tests\Unit\Helper;

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

    private function createSystemHelperWithOptions(array $options): SystemHelper
    {
        $mockOptionRepository = $this->getMockBuilder(OptionRepository::class)->disableOriginalConstructor()->getMock();
        $mockOptionRepository->method('findOneBy')->willReturnCallback(
            function ($criteria) use ($options) {
                if (!isset($options[$criteria['name']])) {
                    return null;
                }
                $option = new Option();
                $option->setValue($options[$criteria['name']]);

                return $option;
            }
        );

        return new SystemHelper($mockOptionRepository);
    }

    public function testGetSSHConnectionDetailsFallsBackToIp(): void
    {
        $systemHelper = $this->createSystemHelperWithOptions(['IP_ADDRESS' => '10.0.0.5']);

        self::assertEquals(['host' => '10.0.0.5', 'port' => 22], $systemHelper->getSSHConnectionDetails());
    }

    public function testGetSSHConnectionDetailsUsesFullClusterHostname(): void
    {
        $systemHelper = $this->createSystemHelperWithOptions([
            'IP_ADDRESS' => '10.0.0.5',
            'CLUSTER' => 'jpt.edgebox.io',
            'USERNAME' => 'jpt',
            'CLUSTER_SSH_PORT' => '2222',
        ]);

        self::assertEquals(['host' => 'jpt.edgebox.io', 'port' => 2222], $systemHelper->getSSHConnectionDetails());
    }

    public function testGetSSHConnectionDetailsComposesClusterHostname(): void
    {
        $systemHelper = $this->createSystemHelperWithOptions([
            'IP_ADDRESS' => '10.0.0.5',
            'CLUSTER' => 'edgebox.io',
            'USERNAME' => 'jpt',
            'CLUSTER_SSH_PORT' => '2222',
        ]);

        self::assertEquals(['host' => 'jpt.edgebox.io', 'port' => 2222], $systemHelper->getSSHConnectionDetails());
    }

    public function testGetSSHConnectionDetailsRejectsInvalidPort(): void
    {
        $systemHelper = $this->createSystemHelperWithOptions([
            'CLUSTER' => 'edgebox.io',
            'CLUSTER_SSH_PORT' => 'not-a-port',
        ]);

        self::assertEquals(['host' => 'edgebox.io', 'port' => 22], $systemHelper->getSSHConnectionDetails());
    }
}
