<?php


declare(strict_types=1);


namespace App\Service;


readonly class ArticleProvider
{
    public function transformData(array $articles): array
    {
        $transformersArticles = [];
        foreach ($articles as $article) {
            $transformersArticles['articles'][] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'content' => substr($article->getContent(), 0, 200) . '...',
            ];
        }


        return $transformersArticles;
    }
}
