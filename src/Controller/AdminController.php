<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/index.html.twig', []);
    }

    #[Route('/admin/usuarios', name: 'app_admin_usuarios')]
    public function usuarios(UserRepository $userRepository): Response
    {
        return $this->render('usuarios/usuarios.html.twig', [
            'usuarios' => array_filter($userRepository->findAll(), function (User $usuario) {
                return in_array('ROLE_USER', $usuario->getRoles());
            }),
        ]);
    }

}
