<?php

namespace App\Controller;

use App\Entity\Competicion;
use App\Entity\Equipo;
use App\Entity\EquipoCompeticion;
use App\Entity\Estadio;
use App\Entity\Plantilla;
use App\Entity\PlantillaJugador;
use App\Repository\EquipoRepository;
use App\Repository\EstadioRepository;
use App\Util\CompruebaParametrosTrait;
use App\Util\ParseaPeticionJsonTrait;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes\Tag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/equipos', name: 'api_equipo_')]
#[Tag(name: 'Equipos')]
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

    #[Route(name: 'listar', methods: ['GET'])]
    public function listaEquipos(): JsonResponse
    {
        $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())));

        return $this->json([
            'equipos' => array_map(function (Equipo $equipo) use ($normalizer) {
                return $normalizer->normalize($equipo, null, ['groups' => 'lista']);
            }, $this->equipoRepository->findAll())
        ]);
    }

    #[Route(name: 'nuevo', methods: ['POST'])]
    public function nuevoEquipo(Request $request, SerializerInterface $serializer): JsonResponse
    {
        if (!$this->peticionConParametrosObligatorios(['nombre', 'nombreCompleto', 'nombreAbreviado', 'pais'], $request)) {
            return $this->creaRespuestaConParametrosObligatoriosInexistentes();
        }

        $this->parseaContenidoPeticionJson($request);

        if ($this->equipoRepository->existeEquipoConNombre($this->contenidoPeticion['nombre'])) {
            return $this->json([
                'msg' => sprintf('Ya existe un equipo con el nombre \'%s\'', $this->contenidoPeticion['nombre']),
            ], 502);
        }

        try {
            $equipo = $serializer->deserialize($request->getContent(), Equipo::class, 'json', [
                DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
            ]);
            $this->equipoRepository->save($equipo, true);

            return $this->json([
                'msg' => 'Equipo guardado correctamente',
                'id' => $equipo->getId(),
            ]);

        } catch (PartialDenormalizationException $e) {
            $errores = [];
            /** @var NotNormalizableValueException $exception */
            foreach ($e->getErrors() as $exception) {
                $errores[] = sprintf("El atributo '%s' debe ser de tipo '%s' ('%s' dado)",
                    $exception->getPath(), implode($exception->getExpectedTypes()), $exception->getCurrentType());
            }
            return $this->json([
                'msg' => 'Error de validación',
                'detalles' => $errores,
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{idEquipo}', name: 'modificar', methods: ['PATCH'])]
    public function modificaEquipo(int $idEquipo, Request $request, SerializerInterface $serializer): JsonResponse
    {
        $equipo = $this->equipoRepository->find($idEquipo);
        if (!$equipo) {
            return $this->json([
                'msg' => 'No existe ningún equipo con el id ' . $idEquipo,
            ], 501);
        }

        try {
            $serializer->deserialize(
                $request->getContent(),
                Equipo::class, 'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $equipo]
            );
            $this->equipoRepository->save($equipo, true);

            return $this->json(['msg' => 'Equipo modificado correctamente']);

        } catch (PartialDenormalizationException $e) {
            $errores = [];
            /** @var NotNormalizableValueException $exception */
            foreach ($e->getErrors() as $exception) {
                $errores[] = sprintf("El atributo '%s' debe ser de tipo '%s' ('%s' dado)",
                    $exception->getPath(), implode($exception->getExpectedTypes()), $exception->getCurrentType());
            }
            return $this->json([
                'msg' => 'Error de validación',
                'detalles' => $errores,
            ], Response::HTTP_BAD_REQUEST);
        }
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

    #[Route('/{idEquipo}/competiciones', name: 'agregar_competicion', methods: ['POST'])]
    public function agregaPlantillaCompeticion(int $idEquipo, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->peticionConParametrosObligatorios(['competicion', 'plantilla',], $request)) {
            return $this->creaRespuestaConParametrosObligatoriosInexistentes();
        }

        $this->parseaContenidoPeticionJson($request);

        $equipo = $this->equipoRepository->find($idEquipo);
        if (!$equipo) {
            return $this->json([
                'msg' => 'No existe ningún equipo con el id ' . $idEquipo,
            ], 264);
        }

        $competicion = $entityManager->getRepository(Competicion::class)->find($this->contenidoPeticion['competicion']);
        if (!$competicion) {
            return $this->json([
                'msg' => 'No existe ninguna competición con el id ' . $this->contenidoPeticion['competicion'],
            ], 264);
        }

        $plantilla = $entityManager->getRepository(Plantilla::class)->find($this->contenidoPeticion['plantilla']);
        if (!$plantilla) {
            return $this->json([
                'msg' => 'No existe ninguna plantilla con el id ' . $this->contenidoPeticion['plantilla'],
            ], 264);
        };

        $equipoCompeticion = $entityManager->getRepository(EquipoCompeticion::class)->load($equipo, $competicion, $plantilla);
        if ($equipoCompeticion) {
            return $this->json([
                'msg' => 'El equipo ya participa en la competición con la misma plantilla',
            ], 264);
        }

        $equipoCompeticion = new EquipoCompeticion();
        $equipoCompeticion
            ->setCompeticion($competicion)
            ->setEquipo($equipo)
            ->setPlantilla($plantilla);

        $entityManager->persist($equipoCompeticion);
        $entityManager->flush();

        return $this->json(['msg' => 'El equipo se ha agregado correctamente a la competición']);
    }

    #[Route('/{idEquipo}/competiciones', name: 'ver_competiciones', methods: ['GET'])]
    public function obtieneCompeticionesEquipo(int $idEquipo): JsonResponse
    {
        $equipo = $this->equipoRepository->find($idEquipo);
        if (!$equipo) {
            return $this->json(['msg' => 'No existe ningún equipo con el ID ' . $idEquipo], 501);
        }

        $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())));

        return $this->json([
            'competiciones' => array_map(function (Competicion $competicion) use ($normalizer) {
                return $normalizer->normalize($competicion, null, ['groups' => 'lista']);
            }, $equipo->getCompeticiones()->toArray()),
        ]);
    }

    #[Route('/eliminaCompeticion', name: 'eliminar_competicion', methods: ['POST'])]
    public function eliminaCompeticionEquipo(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->peticionConParametrosObligatorios(['equipo', 'competicion', 'plantilla'], $request)) {
            return $this->creaRespuestaConParametrosObligatoriosInexistentes();
        }

        $this->parseaContenidoPeticionJson($request);

        $equipo = $this->equipoRepository->find($this->contenidoPeticion['equipo']);
        if (!$equipo) {
            return $this->json([
                'msg' => 'No existe ningún equipo con el id ' . $this->contenidoPeticion['equipo'],
            ], 264);
        }

        $competicion = $entityManager->getRepository(Competicion::class)->find($this->contenidoPeticion['competicion']);
        if (!$competicion) {
            return $this->json([
                'msg' => 'No existe ninguna competición con el id ' . $this->contenidoPeticion['competicion'],
            ], 264);
        }

        $plantilla = $entityManager->getRepository(Plantilla::class)->find($this->contenidoPeticion['plantilla']);
        if (!$plantilla) {
            return $this->json([
                'msg' => 'No existe ninguna plantilla con el id ' . $this->contenidoPeticion['plantilla'],
            ], 264);
        };

        $equipoCompeticion = $entityManager->getRepository(EquipoCompeticion::class)
            ->load($equipo, $competicion, $plantilla);

        if (!$equipoCompeticion) {
            return $this->json([
                'msg' => 'No existe ninguna relación entre el equipo, competición y plantilla solicitados.',
            ], 502);
        }

        $entityManager->remove($equipoCompeticion);
        $entityManager->flush();

        return $this->json([
            'msg' => 'El equipo ha dejado de participar en la competición con ID ' . $this->contenidoPeticion['competicion'],
        ]);
    }

    #[Route('/{idEquipo}/estadios', name: 'estadios', requirements: ['idEquipo' => Requirement::DIGITS], methods: ['GET'])]
    public function estadios(int $idEquipo, EstadioRepository $estadioRepository): Response
    {
        $equipo = $this->equipoRepository->find($idEquipo);
        if (!$equipo) {
            return $this->json([
                'msg' => 'No existe ningún equipo con el id ' . $idEquipo,
            ], 264);
        }

        try {
            $estadios = $estadioRepository->getTodosLosEstadiosDelEquipoconId($idEquipo);
            $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())));

            return $this->json([
                'estadios' => array_map(function (Estadio $estadio) use ($normalizer) {
                    return $normalizer->normalize($estadio, null, ['groups' => 'lista']);
                }, $estadios)
            ]);

        } catch (\Exception $ex) {
            return $this->json([
                'msg' => 'Se ha producido un error al ejecutar la petición',
                'error' => $ex,
            ], 500);
        }
    }

    #[Route('/estadio', name: 'set_estadio_actual', methods: ['POST'])]
    public function setEstadioActual(Request $request, EstadioRepository $estadioRepository): Response
    {
        if (!$this->peticionConParametrosObligatorios(['equipo', 'estadio'], $request)) {
            return $this->creaRespuestaConParametrosObligatoriosInexistentes();
        }

        $this->parseaContenidoPeticionJson($request);

        $equipo = $this->equipoRepository->find($this->contenidoPeticion['equipo']);
        if (!$equipo) {
            return $this->json([
                'msg' => 'No existe ningún equipo con el id ' . $this->contenidoPeticion['equipo'],
            ], 264);
        }

        $estadio = $estadioRepository->find($this->contenidoPeticion['estadio']);
        if (!$estadio) {
            return $this->json([
                'msg' => 'No existe ningún estadio con el id ' . $this->contenidoPeticion['estadio'],
            ], 264);
        }

        try {
            $estadioRepository->guardaRelacionConEquipo($estadio, $equipo->getId());
            $estadioRepository->setEstadioEnUsoParaEquipoConId($estadio, $equipo->getId());

            return $this->json([
                'msg' => 'Estadio establecido correctamente',
            ]);

        } catch (\Exception $ex) {
            return $this->json([
                'msg' => 'Se ha producido un error al ejecutar la petición',
                'error' => $ex,
            ], 500);
        }
    }

    #[Route('/{idEquipo}/estadio', name: 'ver_estadio_actual', requirements: ['idEquipo' => Requirement::DIGITS], methods: ['GET'])]
    public function getEstadioActual(int $idEquipo, EstadioRepository $estadioRepository): Response
    {
        $equipo = $this->equipoRepository->find($idEquipo);
        if (!$equipo) {
            return $this->json([
                'msg' => 'No existe ningún equipo con el id ' . $idEquipo,
            ], 264);
        }

        try {
            $estadio = $estadioRepository->getEstadioActualDelEquipoConId($idEquipo);

            return $this->json($estadio);

        } catch (\Exception $ex) {
            return $this->json([
                'msg' => 'Ha ocurrido un error al realizar la petición',
                'error' => $ex,
            ], 500);
        }
    }

    #[Route('/{idEquipo}/plantillas', name: 'plantillas', requirements: ['idEquipo' => Requirement::DIGITS], methods: ['GET'])]
    public function getPlantillas(int $idEquipo, EntityManagerInterface $entityManager, NormalizerInterface $normalizer): Response
    {
        $equipo = $this->equipoRepository->find($idEquipo);
        if (!$equipo) {
            return $this->json([
                'msg' => 'No existe ningún equipo con el id ' . $idEquipo,
            ]);
        }

        $plantillas = array_map(function (EquipoCompeticion $equipoCompeticion) {
            return $equipoCompeticion->getPlantilla();
        }, $entityManager->getRepository(EquipoCompeticion::class)->findBy(['equipo' => $equipo,]));

        //$normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())));

        return $this->json(
            array_map(function (Plantilla $plantilla) use ($normalizer) {
                return [
                    'id_plantilla' => $plantilla->getId(),
                    'numero_jugadores' => $plantilla->getJugadores()->count(),
                    'jugadores' => $plantilla->getJugadores()->map(function (PlantillaJugador $plantillaJugador) use ($normalizer) {
                        return array_merge(
                            $normalizer->normalize($plantillaJugador->getJugador(), null, ['groups' => 'lista']),
                            ['dorsal' => $plantillaJugador->getDorsal()]
                        );
                    }),
                ];
            }, $plantillas)
        );
    }
}
