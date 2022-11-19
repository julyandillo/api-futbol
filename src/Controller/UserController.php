<?php

namespace App\Controller;

use App\Entity\Aplicacion;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
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
    public function guardaAplicacion(Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        if (!$request->request->has('nombre')) {
            return $this->json([
                'code' => 501,
                'msg' => 'No se puede crear una aplicación sin nombre',
            ]);
        }

        $user = $this->getUser();
        $nombreAplicacion = $request->request->get('nombre');

        if ($this->entityManager->getRepository(Aplicacion::class)->findOneBy([
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

        return $this->json([
            'code' => 200,
            'html_aplicacion' => $this->renderView('user/aplicacion.html.twig', ['aplicacion' => $aplicacion]),
        ]);
    }

    #[Route('/token-aplicacion', name: 'user_token_aplicacion', methods: ['POST'])]
    public function generaTokenParaAplicacion(Request $request, JWTTokenManagerInterface $JWTManager): Response
    {
        if (!$request->request->has('id_aplicacion')) {
            return $this->json([
                'code' => 400,
                'msg' => 'No se puede realizar la peticion, falta el parámetro "id_aplicacion"',
            ]);
        }

        $aplicacion = $this->entityManager
            ->getRepository(Aplicacion::class)
            ->find($request->request->get('id_aplicacion'));

        if (!$aplicacion) {
            return $this->json([
                'code' => 401,
                'msg' => 'No se puede generar el token, no existe la aplicación',
            ]);
        }

        $usuarioAplicacion = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['hash' => $aplicacion->getHash()]);

        if (!$usuarioAplicacion) {
            return $this->json([
                'code' => 401,
                'msg' => 'No se puede generar el token, no existe ningún usuario asociado a la aplicación',
            ]);
        }

        return $this->json([
            'code' => 200,
            'token' => $JWTManager->create($usuarioAplicacion),
        ]);
    }

    #[Route('/elimina-aplicacion', name: 'user_elimina_aplicacion', methods: ['DELETE'])]
    public function eliminaAplicacion(Request $request): Response
    {
        if (!$request->request->has('id_aplicacion')) {
            return $this->json([
                'code' => 400,
                'msg' => 'No se realizar la petición, falta el parámetro "id_aplicacion"',
            ]);
        }

        $aplicacion = $this->entityManager
            ->getRepository(Aplicacion::class)
            ->find($request->request->get('id_aplicacion'));

        if (!$aplicacion) {
            return $this->json([
                'code' => 501,
                'msg' => 'No existe la aplicación',
            ]);
        }

        $this->entityManager->getRepository(Aplicacion::class)->remove($aplicacion);

        $usuarioAplicacion = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['hash' => $aplicacion->getHash()]);

        if ($usuarioAplicacion) {
            $this->entityManager->getRepository(User::class)->remove($usuarioAplicacion);
        }

        $this->entityManager->flush();

        return $this->json(['code' => 200]);
    }
}
