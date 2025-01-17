<?php

namespace App\Controller;

use App\Entity\User;
use App\Formatter\ApiResponseFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseFormatter $apiResponseFormatter,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator
    ) {}

    #[Route('/users/me', name: 'app_current_user', methods: ['GET'])]
    public function getCurrentUser(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->apiResponseFormatter
            ->setMessage('Current user retrieved successfully')
            ->setStatusCode(Response::HTTP_OK)
            ->setData([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ]);

        return new JsonResponse($this->apiResponseFormatter->getResponse());
    }

    #[Route('/users/me', name: 'app_current_user_update', methods: ['PUT'])]
    public function updateCurrentUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            $this->apiResponseFormatter
                ->setMessage('Missing required fields')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setErrors(['email is required']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_BAD_REQUEST
            );
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setEmail($data['email']);

        // Validate the user entity
        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            $this->apiResponseFormatter
                ->setMessage('Validation failed')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setErrors($errors);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $this->entityManager->flush();

            $this->apiResponseFormatter
                ->setMessage('User updated successfully')
                ->setStatusCode(Response::HTTP_OK)
                ->setData([
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles()
                ]);

            return new JsonResponse($this->apiResponseFormatter->getResponse());
        } catch (\Exception $e) {
            $this->apiResponseFormatter
                ->setMessage('Could not update user')
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setErrors([$e->getMessage()]);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/users/me/change-password', name: 'app_current_user_change_password', methods: ['POST'])]
    public function changePassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['current_password']) || !isset($data['new_password'])) {
            $this->apiResponseFormatter
                ->setMessage('Missing required fields')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setErrors(['current_password and new_password are required']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_BAD_REQUEST
            );
        }

        /** @var User $user */
        $user = $this->getUser();

        // Verify current password
        if (!$this->passwordHasher->isPasswordValid($user, $data['current_password'])) {
            $this->apiResponseFormatter
                ->setMessage('Invalid current password')
                ->setStatusCode(Response::HTTP_UNAUTHORIZED)
                ->setErrors(['The current password is incorrect']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Set new password
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $data['new_password'])
        );

        try {
            $this->entityManager->flush();

            $this->apiResponseFormatter
                ->setMessage('Password changed successfully')
                ->setStatusCode(Response::HTTP_OK);

            return new JsonResponse($this->apiResponseFormatter->getResponse());
        } catch (\Exception $e) {
            $this->apiResponseFormatter
                ->setMessage('Could not change password')
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setErrors([$e->getMessage()]);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/users', name: 'app_users_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'You are not authorized to access this resource', statusCode: 403)]
    public function listUsers(): JsonResponse
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();

        $usersData = array_map(function (User $user) {
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ];
        }, $users);

        $this->apiResponseFormatter
            ->setMessage('Users retrieved successfully')
            ->setStatusCode(Response::HTTP_OK)
            ->setData(['users' => $usersData]);

        return new JsonResponse($this->apiResponseFormatter->getResponse());
    }

    #[Route('/users/{id}', name: 'app_user_get', methods: ['GET'])]
    public function getUserDetails(int $id): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            $this->apiResponseFormatter
                ->setMessage('User not found')
                ->setStatusCode(Response::HTTP_NOT_FOUND)
                ->setErrors(['User with id ' . $id . ' does not exist']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_NOT_FOUND
            );
        }

        // Users can only view their own data unless they are admins
        if ($user !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->apiResponseFormatter
                ->setMessage('Access denied')
                ->setStatusCode(Response::HTTP_FORBIDDEN)
                ->setErrors(['You can only view your own user data']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_FORBIDDEN
            );
        }

        $this->apiResponseFormatter
            ->setMessage('User retrieved successfully')
            ->setStatusCode(Response::HTTP_OK)
            ->setData([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ]);

        return new JsonResponse($this->apiResponseFormatter->getResponse());
    }
}
