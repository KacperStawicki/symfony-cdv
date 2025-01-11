<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ArticlesRepository;
use App\Service\ArticleProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BlogController extends AbstractController
{
    public function __construct(
        private ArticlesRepository $articlesRepository,
        private ArticleProvider $articleProvider
    ) {}

    #[Route('/main-page', name: 'main_page')]
    public function mainPage(): Response
    {
        $articles = $this->articlesRepository->findAll();
        $parameters = [];

        if ($articles) {
            $parameters = $this->articleProvider->transformData($articles);
        }

        return $this->render('articles/index.html.twig', $parameters);
    }
}
