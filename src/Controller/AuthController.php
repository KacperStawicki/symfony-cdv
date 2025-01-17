<?php

namespace App\Controller;

use App\Entity\User;
use App\Formatter\ApiResponseFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ApiResponseFormatter $apiResponseFormatter
    ) {}

    #[Route('/register', name: 'app_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            $this->apiResponseFormatter
                ->setMessage('Missing required fields')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setErrors(['email and password are required']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $data['password'])
        );

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->apiResponseFormatter
                ->setMessage('User registered successfully')
                ->setStatusCode(Response::HTTP_CREATED)
                ->setData([
                    'user_id' => $user->getId(),
                    'email' => $user->getEmail()
                ]);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            $this->apiResponseFormatter
                ->setMessage('Could not register user')
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setErrors([$e->getMessage()]);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
