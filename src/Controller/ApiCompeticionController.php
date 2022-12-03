<?php

namespace App\Controller;

use App\Entity\Competicion;
use App\Entity\Equipo;
use App\Repository\CompeticionRepository;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

#[Route('/api/competicion', name: 'api_competicion_')]
class ApiCompeticionController extends AbstractController
{

    public function __construct(private readonly CompeticionRepository $competicionRepository)
    {
    }

    #[Route('/todas', name: 'ver_todas', methods: ['GET'])]
    public function listaCompeticiones(): JsonResponse
    {
        $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())));

        return $this->json(array_map(function (Competicion $competicion) use ($normalizer) {
            return $normalizer->normalize($competicion, null, ['groups' => 'lista']);
        }, $this->competicionRepository->findAll()));
    }

    #[Route('/{idCompeticion}/equipos', name: 'equipos', methods: ['GET'])]
    public function listaEquiposCompeticion(int $idCompeticion): JsonResponse
    {
        $competicion = $this->competicionRepository->find($idCompeticion);
        if (!$competicion) {
            return $this->json([
                'msg' => 'No existe una competición con el id ' . $idCompeticion,
            ], 501);
        }

        $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())));
        return $this->json(array_map(function (Equipo $equipo) use ($normalizer) {
            return $normalizer->normalize($equipo, null, ['groups' => 'lista']);
        }, $competicion->getEquipos()->toArray()));
    }
}
