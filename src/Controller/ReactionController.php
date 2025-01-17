<?php

namespace App\Controller;

use App\Entity\Reaction;
use App\Formatter\ApiResponseFormatter;
use App\Repository\ArticlesRepository;
use App\Repository\ReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ReactionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ApiResponseFormatter $apiResponseFormatter,
        private readonly ArticlesRepository $articlesRepository,
        private readonly ReactionRepository $reactionRepository
    ) {}

    #[Route('/articles/{articleId}/reactions', name: 'api_reactions_list', methods: ['GET'])]
    public function getReactions(int $articleId): JsonResponse
    {
        $article = $this->articlesRepository->find($articleId);
        if (!$article) {
            $this->apiResponseFormatter
                ->setMessage('Article not found')
                ->setStatusCode(Response::HTTP_NOT_FOUND)
                ->setErrors(['Article with id ' . $articleId . ' does not exist']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_NOT_FOUND
            );
        }

        $reactions = $this->reactionRepository->findByArticle($article);
        $counts = $this->reactionRepository->getReactionCounts($article);

        $transformedReactions = array_map(function (Reaction $reaction) {
            return [
                'id' => $reaction->getId(),
                'type' => $reaction->getType(),
                'created_at' => $reaction->getCreatedAt()->format('Y-m-d H:i:s'),
                'user' => [
                    'id' => $reaction->getUser()->getId(),
                    'email' => $reaction->getUser()->getEmail()
                ]
            ];
        }, $reactions);

        $this->apiResponseFormatter
            ->setMessage('Reactions retrieved successfully')
            ->setStatusCode(Response::HTTP_OK)
            ->setData([
                'reactions' => $transformedReactions,
                'counts' => $counts,
                'user_reaction' => $this->getCurrentUserReaction($article)
            ]);

        return new JsonResponse($this->apiResponseFormatter->getResponse());
    }

    #[Route('/articles/{articleId}/reactions', name: 'api_reactions_create', methods: ['POST'])]
    public function addReaction(Request $request, int $articleId): JsonResponse
    {
        $article = $this->articlesRepository->find($articleId);
        if (!$article) {
            $this->apiResponseFormatter
                ->setMessage('Article not found')
                ->setStatusCode(Response::HTTP_NOT_FOUND)
                ->setErrors(['Article with id ' . $articleId . ' does not exist']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_NOT_FOUND
            );
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['type']) || !in_array($data['type'], [Reaction::LIKE, Reaction::DISLIKE])) {
            $this->apiResponseFormatter
                ->setMessage('Invalid reaction type')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setErrors(['type must be either "like" or "dislike"']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_BAD_REQUEST
            );
        }

        // Check if user already reacted to this article
        $existingReaction = $this->reactionRepository->findByArticleAndUser($article, $this->getUser());
        if ($existingReaction) {
            // Update existing reaction if type is different
            if ($existingReaction->getType() !== $data['type']) {
                $existingReaction->setType($data['type']);
                $this->entityManager->flush();

                $this->apiResponseFormatter
                    ->setMessage('Reaction updated successfully')
                    ->setStatusCode(Response::HTTP_OK)
                    ->setData([
                        'reaction' => [
                            'id' => $existingReaction->getId(),
                            'type' => $existingReaction->getType(),
                            'created_at' => $existingReaction->getCreatedAt()->format('Y-m-d H:i:s')
                        ],
                        'counts' => $this->reactionRepository->getReactionCounts($article)
                    ]);

                return new JsonResponse($this->apiResponseFormatter->getResponse());
            }

            // Remove reaction if type is the same (toggle)
            $this->entityManager->remove($existingReaction);
            $this->entityManager->flush();

            $this->apiResponseFormatter
                ->setMessage('Reaction removed successfully')
                ->setStatusCode(Response::HTTP_OK)
                ->setData([
                    'counts' => $this->reactionRepository->getReactionCounts($article)
                ]);

            return new JsonResponse($this->apiResponseFormatter->getResponse());
        }

        try {
            $reaction = new Reaction();
            $reaction->setArticle($article);
            $reaction->setUser($this->getUser());
            $reaction->setType($data['type']);
            $reaction->setCreatedAt(new \DateTime());

            $this->entityManager->persist($reaction);
            $this->entityManager->flush();

            $this->apiResponseFormatter
                ->setMessage('Reaction added successfully')
                ->setStatusCode(Response::HTTP_CREATED)
                ->setData([
                    'reaction' => [
                        'id' => $reaction->getId(),
                        'type' => $reaction->getType(),
                        'created_at' => $reaction->getCreatedAt()->format('Y-m-d H:i:s')
                    ],
                    'counts' => $this->reactionRepository->getReactionCounts($article)
                ]);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            $this->apiResponseFormatter
                ->setMessage('Could not add reaction')
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setErrors([$e->getMessage()]);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function getCurrentUserReaction($article): ?array
    {
        $reaction = $this->reactionRepository->findByArticleAndUser($article, $this->getUser());

        if (!$reaction) {
            return null;
        }

        return [
            'id' => $reaction->getId(),
            'type' => $reaction->getType(),
            'created_at' => $reaction->getCreatedAt()->format('Y-m-d H:i:s')
        ];
    }
}
