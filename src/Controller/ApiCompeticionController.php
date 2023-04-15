<?php

namespace App\Controller;

use App\Entity\Competicion;
use App\Entity\Equipo;
use App\Entity\EquipoCompeticion;
use App\Entity\Plantilla;
use App\Repository\CompeticionRepository;
use App\Util\CompruebaParametrosTrait;
use App\Util\ParseaPeticionJsonTrait;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

#[Route('/api/competicion', name: 'api_competicion_')]
#[OA\Tag(name: 'Competiciones')]
class ApiCompeticionController extends AbstractController
{
    use CompruebaParametrosTrait;
    use ParseaPeticionJsonTrait;

    public function __construct(private readonly CompeticionRepository $competicionRepository)
    {
    }

    #[Route('/{idCompeticion}', name: 'detalles', requirements: ['idCompeticion' => Requirement::DIGITS], methods: ['GET'])]
    public function detalles(int $idCompeticion): JsonResponse
    {
        $competicion = $this->competicionRepository->find($idCompeticion);
        if (!$competicion) {
            return $this->json([
                'msg' => 'No existe ninguna competición con el ID ' . $idCompeticion,
            ], 264);
        }

        return $this->json($competicion);
    }

    #[Route('/todas', name: 'ver_todas', methods: ['GET'])]
    public function listaCompeticiones(): Response
    {
        $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())));

        return $this->json(array_map(function (Competicion $competicion) use ($normalizer) {
            return $normalizer->normalize($competicion, null, ['groups' => 'lista']);
        }, $this->competicionRepository->findAll()));
    }

    #[Route('/{idCompeticion}/equipos', name: 'equipos', requirements: ['idCompeticion' => Requirement::DIGITS], methods: ['GET'])]
    public function listaEquiposCompeticion(int $idCompeticion): Response
    {
        $competicion = $this->competicionRepository->find($idCompeticion);
        if (!$competicion) {
            return $this->json([
                'msg' => 'No existe una competición con el id ' . $idCompeticion,
            ], 264);
        }

        $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())));
        return $this->json(array_map(function (Equipo $equipo) use ($normalizer) {
            return $normalizer->normalize($equipo, null, ['groups' => 'lista']);
        }, $competicion->getEquipos()->toArray()));
    }

    #[Route('/{idCompeticion}', name: '_agregar_equipos', requirements: ['idCompeticion' => Requirement::DIGITS], methods: ['POST'])]
    public function agregaEquipos(int                    $idCompeticion,
                                  Request                $request,
                                  EntityManagerInterface $entityManager): Response
    {
        if (!$this->peticionConParametrosObligatorios(['equipos'], $request)) {
            return $this->json([
                'msg' => 'No se puede realizar la petición, falta el campo \'equipos\'',
            ], 400);
        }

        $this->parseaContenidoPeticionJson($request);

        if (!is_array($this->contenidoPeticion['equipos'])) {
            return $this->json([
                'msg' => 'No se puede realizar la petición, el campo \'equipos\' no es un array',
            ], 400);
        }

        $competicion = $this->competicionRepository->find($idCompeticion);

        $errores = [];
        $posicion = 0;
        $alMenosUnEquipoAsociado = false;
        foreach ($this->contenidoPeticion['equipos'] as $equipoPlantilla) {
            $posicion++;

            if (!array_key_exists('id_equipo', $equipoPlantilla)
                || !array_key_exists('id_plantilla', $equipoPlantilla)) {
                $errores[] = [
                    'posicion' => $posicion,
                    'msg' => 'Para poder asociar un equipo a la competición también es necesario una plantilla',
                ];
                continue;
            }

            $equipo = $entityManager->getRepository(Equipo::class)->find($equipoPlantilla['id_equipo']);
            if (!$equipo) {
                $errores[] = [
                    'equipo' => $equipoPlantilla['id_equipo'],
                    'msg' => 'No existe ningún equipo con este id',
                ];
                continue;
            }

            $plantilla = $entityManager->getRepository(Plantilla::class)->find($equipoPlantilla['id_plantilla']);
            if (!$plantilla) {
                $errores[] = [
                    'plantilla' => $equipoPlantilla['id_plantilla'],
                    'msg' => 'No exste ninguna plantilla con este id',
                ];
                continue;
            }

            if ($entityManager->getRepository(EquipoCompeticion::class)->load($equipo, $competicion, $plantilla)) {
                $errores[] = [
                    'equipo' => $equipoPlantilla['id_equipo'],
                    'error' => 'El equipo ya tiene asociada una plantilla a esta cometición',
                ];
                continue;
            }

            $equipoCompeticion = new EquipoCompeticion();
            $equipoCompeticion
                ->setCompeticion($competicion)
                ->setEquipo($equipo)
                ->setPlantilla($plantilla);

            $entityManager->persist($equipoCompeticion);
            $alMenosUnEquipoAsociado = true;
        }

        $entityManager->flush();

        $response = [
            'msg' => $alMenosUnEquipoAsociado
                ? 'Equipos agregados correctamente a la competición'
                : 'No se ha podido asociar ningún equipo a la competición',
        ];

        if (!empty($errores)) {
            $response['msg'] .= ' (con errores)';
            $response['errores'] = $errores;
        }

        return $this->json($response);
    }
}
