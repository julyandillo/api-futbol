<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ApiLoginController extends AbstractController
{
    #[Route('/api/autenticacion', name: 'api_login_check', methods: ['POST'])]
     public function apiLoginCheck()
     {
         // solo creo la ruta para que el bundle JWT la intercepte
         // no funciona poniendola en routing.yaml
     }
}
