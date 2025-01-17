<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Formatter\ApiResponseFormatter;
use App\Repository\ArticlesRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class CommentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ApiResponseFormatter $apiResponseFormatter,
        private ArticlesRepository $articlesRepository,
        private CommentRepository $commentRepository
    ) {}

    #[Route('/articles/{articleId}/comments', name: 'api_comments_list', methods: ['GET'])]
    public function getComments(int $articleId): JsonResponse
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

        $comments = $this->commentRepository->findBy(['article' => $article], ['createdAt' => 'DESC']);
        $transformedComments = array_map(function (Comment $comment) {
            return [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'created_at' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                'author' => [
                    'id' => $comment->getAuthor()->getId(),
                    'email' => $comment->getAuthor()->getEmail()
                ]
            ];
        }, $comments);

        $this->apiResponseFormatter
            ->setMessage('Comments retrieved successfully')
            ->setStatusCode(Response::HTTP_OK)
            ->setData(['comments' => $transformedComments]);

        return new JsonResponse($this->apiResponseFormatter->getResponse());
    }

    #[Route('/articles/{articleId}/comments', name: 'api_comments_create', methods: ['POST'])]
    public function createComment(Request $request, int $articleId): JsonResponse
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
        if (!isset($data['content'])) {
            $this->apiResponseFormatter
                ->setMessage('Missing required fields')
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setErrors(['content is required']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_BAD_REQUEST
            );
        }

        $comment = new Comment();
        $comment->setContent($data['content']);
        $comment->setCreatedAt(new \DateTime());
        $comment->setAuthor($this->getUser());
        $comment->setArticle($article);

        try {
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $this->apiResponseFormatter
                ->setMessage('Comment created successfully')
                ->setStatusCode(Response::HTTP_CREATED)
                ->setData([
                    'id' => $comment->getId(),
                    'content' => $comment->getContent(),
                    'created_at' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                    'author' => [
                        'id' => $comment->getAuthor()->getId(),
                        'email' => $comment->getAuthor()->getEmail()
                    ]
                ]);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            $this->apiResponseFormatter
                ->setMessage('Could not create comment')
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setErrors([$e->getMessage()]);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/comments/{id}', name: 'api_comments_delete', methods: ['DELETE'])]
    public function deleteComment(int $id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            $this->apiResponseFormatter
                ->setMessage('Comment not found')
                ->setStatusCode(Response::HTTP_NOT_FOUND)
                ->setErrors(['Comment with id ' . $id . ' does not exist']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_NOT_FOUND
            );
        }

        if ($comment->getAuthor() !== $this->getUser()) {
            $this->apiResponseFormatter
                ->setMessage('Access denied')
                ->setStatusCode(Response::HTTP_FORBIDDEN)
                ->setErrors(['You can only delete your own comments']);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_FORBIDDEN
            );
        }

        try {
            $this->entityManager->remove($comment);
            $this->entityManager->flush();

            $this->apiResponseFormatter
                ->setMessage('Comment deleted successfully')
                ->setStatusCode(Response::HTTP_OK);

            return new JsonResponse($this->apiResponseFormatter->getResponse());
        } catch (\Exception $e) {
            $this->apiResponseFormatter
                ->setMessage('Could not delete comment')
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setErrors([$e->getMessage()]);

            return new JsonResponse(
                $this->apiResponseFormatter->getResponse(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/comments/me', name: 'api_my_comments_list', methods: ['GET'])]
    public function getMyComments(): JsonResponse
    {
        $comments = $this->commentRepository->findBy(['author' => $this->getUser()], ['createdAt' => 'DESC']);
        $transformedComments = array_map(function (Comment $comment) {
            return [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'created_at' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                'article' => [
                    'id' => $comment->getArticle()->getId(),
                    'title' => $comment->getArticle()->getTitle()
                ]
            ];
        }, $comments);

        $this->apiResponseFormatter
            ->setMessage('User comments retrieved successfully')
            ->setStatusCode(Response::HTTP_OK)
            ->setData(['comments' => $transformedComments]);

        return new JsonResponse($this->apiResponseFormatter->getResponse());
    }
}
