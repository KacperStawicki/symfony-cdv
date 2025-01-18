<?php

namespace App\Tests\Controller;

use App\Entity\Articles;
use App\Entity\Reaction;
use App\Entity\User;
use App\Tests\DatabaseDependantTestCase;
use Symfony\Component\HttpFoundation\Response;

class ReactionControllerTest extends DatabaseDependantTestCase
{
    private $testUser;
    private $testArticle;
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

        // Create a test article
        $this->testArticle = new Articles();
        $this->testArticle->setTitle('Test Article');
        $this->testArticle->setContent('Test Content');
        $this->testArticle->setCreated(new \DateTime());
        $this->testArticle->setAuthor($this->testUser);

        $this->entityManager->persist($this->testArticle);
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

    public function testGetReactions(): void
    {
        $this->client->request(
            'GET',
            '/api/articles/' . $this->testArticle->getId() . '/reactions',
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
        $this->assertArrayHasKey('counts', $response['data']);
    }

    public function testAddReaction(): void
    {
        $this->client->request(
            'POST',
            '/api/articles/' . $this->testArticle->getId() . '/reactions',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'type' => Reaction::LIKE
            ])
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Reaction::LIKE, $response['data']['reaction']['type']);
    }

    public function testToggleReaction(): void
    {
        // First, add a reaction
        $this->client->request(
            'POST',
            '/api/articles/' . $this->testArticle->getId() . '/reactions',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'type' => Reaction::LIKE
            ])
        );

        // Then, toggle it by sending the same reaction
        $this->client->request(
            'POST',
            '/api/articles/' . $this->testArticle->getId() . '/reactions',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'type' => Reaction::LIKE
            ])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('counts', $response['data']);
        $this->assertEquals(0, $response['data']['counts'][Reaction::LIKE]);
    }
}
