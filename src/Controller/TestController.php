<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class TestController extends AbstractController
{

    #[Route("/test", name: "test")]
    public function index(): Response
    {
        return new Response('Hello World');
    }
}
