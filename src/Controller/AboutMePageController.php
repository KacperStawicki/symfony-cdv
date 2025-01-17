<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AboutMePageController extends AbstractController
{
    #[Route('/about/me/page', name: 'app_about_me_page')]
    public function index(): Response
    {
        return $this->render('about_me_page/index.html.twig', [
            'controller_name' => 'AboutMePageController',
        ]);
    }
}
