<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class AdminController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/index.html.twig', []);
    }

    #[Route('/admin/usuarios', name: 'app_admin_usuarios')]
    public function usuarios(UserRepository $userRepository): Response
    {
        return $this->render('admin/usuarios.html.twig', [
            'usuarios' => array_filter($userRepository->findAll(), static function (User $usuario) {
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
                'msg' => $this->translator->trans('generic.400', ['%params%' => 'usuario'], 'messages'),
                ]);
        }

        $usuario = $userRepository->findOneBy(['usuario' => $request->request->get('usuario')]);

        if (!$usuario) {
            return $this->json([
                'code' => 401,
                'msg' => $this->translator->trans('admin.delete', ['%username%' => $request->request->get('usuario')], 'messages'),
            ]);
        }

        $userRepository->remove($usuario);

        return $this->json(['code' => 200]);
    }

}
