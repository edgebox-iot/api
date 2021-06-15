<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StorageControllerTest extends WebTestCase
{
    public function testHomeRoute(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storage');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testAddNewDeviceRoute(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storage/device/new');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
