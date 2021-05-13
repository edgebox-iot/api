<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ControllerTest extends WebTestCase
{
    public function testHomeAction(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
