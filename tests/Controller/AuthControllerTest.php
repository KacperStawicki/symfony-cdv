<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\DatabaseDependantTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends DatabaseDependantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testRegisterUser(): void
    {
        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'newuser@test.com',
                'password' => 'test123'
            ])
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('newuser@test.com', $response['data']['email']);
    }

    public function testLoginUser(): void
    {
        // Create a test user first
        $user = new User();
        $user->setEmail('logintest@test.com');
        $user->setPassword(
            $this->client->getContainer()->get('security.user_password_hasher')->hashPassword(
                $user,
                'test123'
            )
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Try to login
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'logintest@test.com',
                'password' => 'test123'
            ])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $response);
    }
}
