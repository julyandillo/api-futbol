<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiEstadioController extends AbstractController
{
    #[Route('/api/estadio', name: 'app_api_estadio')]
    public function index(): Response
    {
        return $this->render('api_estadio/index.html.twig', [
            'controller_name' => 'ApiEstadioController',
        ]);
    }
}
