<?php

namespace App\Controller;

use App\Entity\Aplicacion;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class UserController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/perfil', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'aplicaciones' => $this->getUser()->getAplicaciones(),
        ]);
    }

    #[Route('/nueva-aplicacion', name: 'user_nueva_aplicacion', methods: ['POST'])]
    public function guardaAplicacion(Request $request, UserInterface $user, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        if (!$request->request->has('nombre')) {
            return $this->json([
                'code' => 501,
                'msg' => 'No se puede crear una aplicación sin nombre',
            ]);
        }

        $nombreAplicacion = $request->request->get('nombre');
        if ($this->entityManager->getRepository(Aplicacion::class)->findBy([
            'nombre' => $nombreAplicacion,
            'usuario' => $user->getId(),
        ])) {
            return $this->json([
                'code' => 502,
                'msg' => 'Ya existe una aplicación con el mismo nombre',
            ]);
        }

        $hash = password_hash(sprintf("%d:%s", random_int(100000, 999999), $nombreAplicacion), PASSWORD_DEFAULT);
        $nombreUsuario = hash('sha256', sprintf("%s:%s", $user->getUsuario(), $nombreAplicacion));

        // con cada aplicación se creará un usuario nuevo para poder generar el token JWT con ese usuario
        $usuarioParaAplicacion = new User();
        $usuarioParaAplicacion
            ->setHash($hash)
            ->setUsuario($nombreUsuario)
            ->setRoles(['ROLE_APP_USER'])
            ->setPassword($userPasswordHasher
                ->hashPassword(
                    $usuarioParaAplicacion,
                    md5($nombreUsuario)
                )
            );

        $aplicacion = new Aplicacion();
        $aplicacion
            ->setNombre($nombreAplicacion)
            ->setUsuario($user) // el usuario al que pertenece la aplicacion
            ->setHash($hash);

        $this->entityManager->persist($usuarioParaAplicacion);
        $this->entityManager->persist($aplicacion);
        $this->entityManager->flush();

        return $this->json(['code' => 200]);
    }
}
