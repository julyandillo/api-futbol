<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        return $this->render('login/index.html.twig', [
            'error' => $error
        ]);
    }

    #[Route('/logout', name:'app_logout')]
    public function logout(): void
    {
        // no es necesario que tenga nada, este método nunca será ejecutado!! el firewal intercetará la ruta y
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    #[Route('/after-login', name: 'app_after_login')]
    public function afterLogin(): Response
    {
        if ($this->getUser()) {
            $destino = $this->isGranted('ROLE_ADMIN') ? 'app_admin' : 'app_profile';

            return $this->redirectToRoute($destino);
        }

        return $this->render('notfound.html.twig');
    }

    #[Route('/404', name: 'app_not_found')]
    public function testNotFound(): Response
    {
        return $this->render('notfound.html.twig');
    }
}
