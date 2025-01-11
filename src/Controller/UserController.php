<?php

namespace App\Controller;

use App\Formatter\ApiResponseFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;

class UserController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseFormatter $apiResponseFormatter
    ) {}

    #[Route('/api/users/show', name: 'app_user', methods: ['GET'])]
    public function showUser(): JsonResponse
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            $this->apiResponseFormatter->setStatusCode(401);
            $this->apiResponseFormatter->setErrors(['User not authenticated']);
            return new JsonResponse($this->apiResponseFormatter->getResponse(), 401);
        }

        $this->apiResponseFormatter->setData([
            'user_id' => $currentUser->getId(),
            'user_email' => $currentUser->getEmail(),
        ]);

        return new JsonResponse($this->apiResponseFormatter->getResponse());
    }
}
