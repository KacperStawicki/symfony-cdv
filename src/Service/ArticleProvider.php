<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ReactionRepository;
use Symfony\Bundle\SecurityBundle\Security;

readonly class ArticleProvider
{
    public function __construct(
        private ReactionRepository $reactionRepository,
        private Security $security
    ) {}

    public function transformData(array $articles): array
    {
        $transformersArticles = [];
        foreach ($articles as $article) {
            $transformersArticles['articles'][] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'content' => substr($article->getContent(), 0, 200) . '...',
                'created' => $article->getCreated()->format('Y-m-d H:i:s'),
                'author' => [
                    'id' => $article->getAuthor()->getId(),
                    'email' => $article->getAuthor()->getEmail()
                ],
                'reactions' => [
                    'counts' => $this->reactionRepository->getReactionCounts($article),
                    'user_reaction' => $this->getCurrentUserReaction($article)
                ]
            ];
        }

        return $transformersArticles;
    }

    public function transformSingleArticle($article): array
    {
        return [
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'created' => $article->getCreated()->format('Y-m-d H:i:s'),
            'author' => [
                'id' => $article->getAuthor()->getId(),
                'email' => $article->getAuthor()->getEmail()
            ],
            'reactions' => [
                'counts' => $this->reactionRepository->getReactionCounts($article),
                'user_reaction' => $this->getCurrentUserReaction($article)
            ]
        ];
    }

    private function getCurrentUserReaction($article): ?array
    {
        $user = $this->security->getUser();
        if (!$user) {
            return null;
        }

        $reaction = $this->reactionRepository->findByArticleAndUser($article, $user);
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
