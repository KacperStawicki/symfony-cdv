<?php

namespace App\Tests\Controller;

use App\Entity\Articles;
use App\Entity\User;
use App\Tests\DatabaseDependantTestCase;
use Symfony\Component\HttpFoundation\Response;

class BlogControllerTest extends DatabaseDependantTestCase
{
    private $testUser;
    private $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->testUser = new User();
        $this->testUser->setEmail('test@test.com');
        $this->testUser->setPassword(
            $this->client->getContainer()->get('security.user_password_hasher')->hashPassword(
                $this->testUser,
                'test123'
            )
        );

        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();

        // Get JWT token
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test@test.com',
                'password' => 'test123'
            ])
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->token = $response['token'];
    }

    public function testGetArticles(): void
    {
        $this->client->request(
            'GET',
            '/api/articles',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
                'CONTENT_TYPE' => 'application/json'
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $response);
    }

    public function testCreateArticle(): void
    {
        $this->client->request(
            'POST',
            '/api/articles',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'title' => 'Test Article',
                'content' => 'Test Content'
            ])
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Article', $response['data']['title']);
    }

    public function testGetSingleArticle(): void
    {
        // Create a test article
        $article = new Articles();
        $article->setTitle('Test Article');
        $article->setContent('Test Content');
        $article->setCreated(new \DateTime());
        $article->setAuthor($this->testUser);

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/articles/' . $article->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
                'CONTENT_TYPE' => 'application/json'
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Article', $response['data']['title']);
    }
}
