<?php

namespace App\Tests;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DatabaseDependantTestCase extends WebTestCase
{
    protected $entityManager;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $container = self::getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();

        // Create database schema
        $this->createSchema($container);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up Doctrine to prevent memory leaks
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }

    private function createSchema(ContainerInterface $container): void
    {
        $entityManager = $container->get('doctrine')->getManager();
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        if (!empty($metadata)) {
            $schemaTool = new SchemaTool($entityManager);
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }
    }
}
