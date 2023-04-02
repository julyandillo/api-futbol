<?php

namespace App\Controller;

use App\Entity\Competicion;
use App\Entity\Equipo;
use App\Entity\EquipoCompeticion;
use App\Entity\Plantilla;
use App\Repository\CompeticionRepository;
use App\Repository\EquipoRepository;
use App\Repository\PlantillaRepository;
use App\Util\CompruebaParametrosTrait;
use App\Util\ParseaPeticionJsonTrait;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

#[Route('/api/competicion', name: 'api_competicion_')]
class ApiCompeticionController extends AbstractController
{
    use CompruebaParametrosTrait;
    use ParseaPeticionJsonTrait;

    public function __construct(private readonly CompeticionRepository $competicionRepository)
    {
    }

    #[Route('/{idCompeticion}', name: 'detalles', requirements: ['idCompeticion' => Requirement::DIGITS], methods: ['GET'])]
    public function detalles(int $idCompeticion): Response
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
    public function agregaEquipos(int                 $idCompeticion,
                                  Request             $request,
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
        foreach ($this->contenidoPeticion['equipos'] as $equipoPlantilla) {
            if (!array_key_exists('id_quipo', $equipoPlantilla)
                || !array_key_exists('id_plantilla', $equipoPlantilla)) {
                continue;
            }

            $equipo = $entityManager->getRepository(Equipo::class)->find($equipoPlantilla['id_equipo']);
            if (!$equipo) {
                continue;
            }

            $plantilla = $entityManager->getRepository(Plantilla::class)->find($equipoPlantilla['id_plantilla']);
            if (!$plantilla) {
                continue;
            }

            if ($entityManager->getRepository(EquipoCompeticion::class)->load($equipo, $competicion, $plantilla)) {
                continue;
            }

            $participacion = new EquipoCompeticion();
            $participacion
                ->setCompeticion($competicion)
                ->setEquipo($equipo)
                ->setPlantilla($plantilla);

            $entityManager->persist($participacion);

        }

        $entityManager->flush();

        return $this->json([
            'msg' => 'Equipos agregados correctamente a la competición',
        ]);
    }
}
