<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Articles;
use App\Repository\ArticlesRepository;
use App\Service\ArticleProvider;
use App\Formatter\ApiResponseFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class BlogController extends AbstractController
{
    public function __construct(
        private ArticlesRepository $articlesRepository,
        private ArticleProvider $articleProvider,
        private ApiResponseFormatter $apiResponseFormatter,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/articles', name: 'api_articles_list', methods: ['GET'])]
    public function getArticles(): JsonResponse
    {
        $articles = $this->articlesRepository->findAll();
        $transformedArticles = [];

        if ($articles) {
            $transformedArticles = $this->articleProvider->transformData($articles);
        }

        $this->apiResponseFormatter
            ->setMessage('Articles retrieved successfully')
            ->setStatusCode(Response::HTTP_OK)
            ->setData($transformedArticles);

        return new JsonResponse($this->apiResponseFormatter->getResponse());
    }

    #[Route('/articles/{id}', name: 'api_articles_get', methods: ['GET'])]
    public function getArticle(int $id): JsonResponse
    {
        $article = $this->articlesRepository->find($id);

        if (!$article) {
            $this->apiResponseFormatter
                ->setMessage('Article not found')
                ->setStatusCode(Response::HTTP_NOT_FOUND)
                ->setErrors(['Article with id ' . $id . ' does not exist']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_NOT_FOUND
            );
        }

        $this->apiResponseFormatter
            ->setMessage('Article retrieved successfully')
            ->setStatusCode(Response::HTTP_OK)
            ->setData($this->articleProvider->transformSingleArticle($article));

        return new JsonResponse($this->apiResponseFormatter->getResponse());
    }

    #[Route('/articles', name: 'api_articles_create', methods: ['POST'])]
    public function createArticle(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || !isset($data['content'])) {
            $this->apiResponseFormatter
                ->setMessage('Missing required fields')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setErrors(['title and content are required']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_BAD_REQUEST
            );
        }

        $article = new Articles();
        $article->setTitle($data['title']);
        $article->setContent($data['content']);
        $article->setCreated(new \DateTime());
        $article->setAuthor($this->getUser());

        try {
            $this->entityManager->persist($article);
            $this->entityManager->flush();

            $this->apiResponseFormatter
                ->setMessage('Article created successfully')
                ->setStatusCode(Response::HTTP_CREATED)
                ->setData($this->articleProvider->transformSingleArticle($article));

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            $this->apiResponseFormatter
                ->setMessage('Could not create article')
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setErrors([$e->getMessage()]);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/articles/{id}', name: 'api_articles_delete', methods: ['DELETE'])]
    public function deleteArticle(int $id): JsonResponse
    {
        $article = $this->articlesRepository->find($id);

        if (!$article) {
            $this->apiResponseFormatter
                ->setMessage('Article not found')
                ->setStatusCode(Response::HTTP_NOT_FOUND)
                ->setErrors(['Article with id ' . $id . ' does not exist']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_NOT_FOUND
            );
        }

        if ($article->getAuthor() !== $this->getUser()) {
            $this->apiResponseFormatter
                ->setMessage('Access denied')
                ->setStatusCode(Response::HTTP_FORBIDDEN)
                ->setErrors(['You can only delete your own articles']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_FORBIDDEN
            );
        }

        try {
            $this->entityManager->remove($article);
            $this->entityManager->flush();

            $this->apiResponseFormatter
                ->setMessage('Article deleted successfully')
                ->setStatusCode(Response::HTTP_OK);

            return new JsonResponse($this->apiResponseFormatter->getResponse());
        } catch (\Exception $e) {
            $this->apiResponseFormatter
                ->setMessage('Could not delete article')
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setErrors([$e->getMessage()]);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
