<?php

namespace App\Controller;

use App\Entity\Competicion;
use App\Entity\Equipo;
use App\Repository\CompeticionRepository;
use App\Repository\EquipoRepository;
use App\Util\CompruebaParametrosTrait;
use App\Util\ParseaPeticionJsonTrait;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/equipo', name: 'api_equipo_')]
class ApiEquipoController extends AbstractController
{
    use CompruebaParametrosTrait;
    use ParseaPeticionJsonTrait;

    public function __construct(private readonly EquipoRepository $equipoRepository)
    {
    }

    #[Route('/{idEquipo}', name: 'ver', requirements: ['idEquipo' => Requirement::DIGITS], methods: ['GET'])]
    public function index(int $idEquipo, NormalizerInterface $normalizer): JsonResponse
    {
        $equipo = $this->equipoRepository->find($idEquipo);

        if (!$equipo) {
            return $this->json([
                'msg' => 'No existe ningún equipo con el ID ' . $idEquipo,
            ], 501);
        }

        //return $this->json(json_decode($serializer->serialize($equipo, 'json'), true));
        return $this->json($normalizer->normalize($equipo));
    }

    #[Route('/todos', name: 'listar', methods: ['GET'])]
    public function listaEquipos(): JsonResponse
    {
        $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())));

        return $this->json(array_map(function (Equipo $equipo) use ($normalizer) {
            return $normalizer->normalize($equipo, null, ['groups' => 'lista']);
        }, $this->equipoRepository->findAll()));
    }

    #[Route(name: 'nuevo', methods: ['POST'])]
    public function nuevoEquipo(Request $request, SerializerInterface $serializer): JsonResponse
    {
        $camposObligatorios = ['nombre', 'nombreCompleto', 'nombreAbreviado', 'pais'];
        $this->parseaContenidoPeticionJson($request);

        if (!$this->peticionConParametrosObligatorios($camposObligatorios, $request)) {
            return $this->json([
                'msg' => sprintf('Campos obligatorios para poder crear el equipo: [%s]',
                    implode(', ', array_diff($camposObligatorios, array_keys($this->contenidoPeticion)))),
            ], 400);
        }

        if ($this->equipoRepository->existeEquipoConNombre($this->contenidoPeticion['nombre'])) {
            return $this->json([
                'msg' => sprintf('Ya existe un equipo con el nombre \'%s\'', $this->contenidoPeticion['nombre']),
            ], 502);
        }

        $equipo = $serializer->deserialize($request->getContent(), Equipo::class, 'json');
        $this->equipoRepository->save($equipo, true);

        return $this->json([
            'msg' => 'Equipo guardado correctamente',
            'id' => $equipo->getId(),
        ]);
    }

    #[Route('/{idEquipo}', name: 'modificar', methods: ['PATCH'])]
    public function modificaEquipo(int $idEquipo, Request $request, SerializerInterface $serializer): JsonResponse
    {
        $equipo = $this->equipoRepository->find($idEquipo);
        if (!$equipo) {
            return $this->json([
                'msg' => 'No existe ningún equipo con el id ' . $idEquipo
            ], 501);
        }

        $serializer->deserialize($request->getContent(), Equipo::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $equipo]);
        $this->equipoRepository->save($equipo, true);

        return $this->json(['msg' => 'Equipo modificado correctamente']);
    }

    #[Route('/{idEquipo}', name: 'eliminar', methods: ['DELETE'])]
    public function eliminaEquipo(int $idEquipo): JsonResponse
    {
        $equipo = $this->equipoRepository->find($idEquipo);

        if (!$equipo) {
            return $this->json(['msg' => 'No existe ningún equipo con el ID ' . $idEquipo], 501);
        }

        $this->equipoRepository->remove($equipo, true);

        return $this->json(['msg' => 'Equipo eliminado correctamente']);
    }

    #[Route('/competiciones', name: 'modificar_competiciones', methods: ['POST'])]
    public function modificaCompeticionesEquipo(Request $request, CompeticionRepository $competicionRepository): JsonResponse
    {
        $this->parseaContenidoPeticionJson($request);

        if (!$this->peticionConParametrosObligatorios(['equipo', 'competiciones'], $request)) {
            return $this->json([
                'msg' => 'Campos obligatorios: equipo y competiciones',
            ], 400);
        }

        $equipo = $this->equipoRepository->find($this->contenidoPeticion['equipo']);
        if (!$equipo) {
            return $this->json(['msg' => 'No existe ningún equipo con el ID ' . $this->contenidoPeticion['equipo']], 501);
        }

        foreach ($this->contenidoPeticion['competiciones'] as $idCompeticion) {
            $competicion = $competicionRepository->find($idCompeticion);
            if (!$competicion) {
                continue;
            }

            $equipo->agregaCompeticionEnLaQueParticipa($competicion);
        }

        $this->equipoRepository->save($equipo, true);

        return $this->json(['msg' => 'Competiciones agregadas correctamente al equipo']);
    }

    #[Route('/competiciones/{idEquipo}', name: 'ver_competiciones', methods: ['GET'])]
    public function muestraCompeticionesEquipo(int $idEquipo): JsonResponse
    {
        $equipo = $this->equipoRepository->find($idEquipo);
        if (!$equipo) {
            return $this->json(['msg' => 'No existe ningún equipo con el ID ' . $idEquipo], 501);
        }

        $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())));

        return $this->json(array_map(function (Competicion $competicion) use ($normalizer) {
            return $normalizer->normalize($competicion, null, ['groups' => 'lista']);
        }, $equipo->getCompeticiones()->toArray()));
    }

    #[Route('/eliminarCompeticion', name: 'eliminar_competicion', methods: ['POST'])]
    public function eliminaCompeticionEquipo(Request $request, CompeticionRepository $competicionRepository): JsonResponse
    {
        $this->parseaContenidoPeticionJson($request);
        if (!$this->peticionConParametrosObligatorios(['equipo', 'competicion'], $request)) {
            return $this->json([
                'msg' => 'Campos obligatorios: equipo y competicion',
            ], 400);
        }

        $equipo = $this->equipoRepository->find($this->contenidoPeticion['equipo']);
        if (!$equipo) {
            return $this->json(['msg' => 'No existe ningún equipo con el ID ' . $this->contenidoPeticion['equipo']], 501);
        }

        $competicionParaEliminar = $competicionRepository->find($this->contenidoPeticion['competicion']);
        if (!$competicionParaEliminar) {
            return $this->json([
                'msg' => 'No existe ninguna competición con el ID ' . $this->contenidoPeticion['competicion'],
            ], 501);
        }

        if (!$equipo->getCompeticiones()->contains($competicionParaEliminar)) {
            return $this->json([
                'msg' => 'El equipo no participa en la competición con ID ' . $this->contenidoPeticion['competicion'],
            ], 502);
        }

        $equipo->getCompeticiones()->remove($equipo->getCompeticiones()->indexOf($competicionParaEliminar));
        $this->equipoRepository->save($equipo, true);

        return $this->json([
            'msg' => 'El equipo ha dejado de participar en la competición con ID ' . $this->contenidoPeticion['competicion'],
        ]);
    }
}
