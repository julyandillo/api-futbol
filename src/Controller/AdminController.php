<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


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

    #[Route('/admin/usuario/delete',name: 'app_admin_eliminar_usuario', methods: ['DELETE'])]
    public function eliminaUsuario(Request $request, UserRepository $userRepository): Response
    {
        if (!$request->request->has('usuario')) {
            return $this->json([
                'code' => 400,
                'msg' => 'No se puede realizar la petición, falta el parámetro "usuario"',
                ]);
        }

        $usuario = $userRepository->findOneBy(['usuario' => $request->request->get('usuario')]);

        if (!$usuario) {
            return $this->json([
                'code' => 401,
                'msg' => spirntf('No existe ningún usuario con el username "%s"', $request->request->get('usuario')),
            ]);
        }

        $userRepository->remove($usuario);

        return $this->json(['code' => 200]);
    }

}
