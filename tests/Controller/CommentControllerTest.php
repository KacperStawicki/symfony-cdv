<?php

namespace App\Tests\Controller;

use App\Entity\Articles;
use App\Entity\Comment;
use App\Entity\User;
use App\Tests\DatabaseDependantTestCase;
use Symfony\Component\HttpFoundation\Response;

class CommentControllerTest extends DatabaseDependantTestCase
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

    public function testGetComments(): void
    {
        $this->client->request(
            'GET',
            '/api/articles/' . $this->testArticle->getId() . '/comments',
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

    public function testCreateComment(): void
    {
        $this->client->request(
            'POST',
            '/api/articles/' . $this->testArticle->getId() . '/comments',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode([
                'content' => 'Test Comment'
            ])
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Comment', $response['data']['content']);
    }

    public function testGetMyComments(): void
    {
        // Create a test comment
        $comment = new Comment();
        $comment->setContent('Test Comment');
        $comment->setCreatedAt(new \DateTime());
        $comment->setAuthor($this->testUser);
        $comment->setArticle($this->testArticle);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/comments/me',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
                'CONTENT_TYPE' => 'application/json'
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('comments', $response['data']);
        $this->assertGreaterThan(0, count($response['data']['comments']));
    }
}
