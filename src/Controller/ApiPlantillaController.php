<?php

namespace App\Controller;

use App\Entity\Jugador;
use App\Entity\Plantilla;
use App\Entity\PlantillaJugador;
use App\Repository\EquipoCompeticionRepository;
use App\Repository\PlantillaRepository;
use App\Util\CompruebaParametrosTrait;
use App\Util\ParseaPeticionJsonTrait;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes\Tag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;


#[Route('/api/plantilla', name: 'api_plantilla_')]
#[Tag(name: 'Plantillas')]
class ApiPlantillaController extends AbstractController
{
    use CompruebaParametrosTrait;
    use ParseaPeticionJsonTrait;

    public function __construct(private readonly PlantillaRepository $plantillaRepository)
    {
    }

    #[Route(name: 'crear', methods: ['POST'])]
    public function nuevaPlantilla(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $this->parseaContenidoPeticionJson($request);
        if (!$this->peticionConParametrosObligatorios(['jugadores',], $request)) {
            return $this->json([
                'msg' => sprintf('No se puede realizar la petición, faltan parámetros obligatorios: [%s]',
                    $this->stringConParametrosFaltantes()),
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($this->contenidoPeticion['jugadores'])) {
            return $this->json([
                'msg' => 'No se puede realizar la petición, \'jugadores\' debe ser un array',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (empty($this->contenidoPeticion['jugadores'])) {
            return $this->json([
                'msg' => 'No se puede crear una plantilla sin jugadores'
            ], Response::HTTP_BAD_REQUEST);
        }

        $errores = [];
        $posicion = 0;
        $plantilla = new Plantilla();

        foreach ($this->contenidoPeticion['jugadores'] as $rawJugador) {
            $posicion++;

            if (!array_key_exists('id', $rawJugador)
                && !empty(array_diff(Jugador::getArrayConCamposObligatorios(), array_keys($rawJugador)))
            ) {
                $errores[] = [
                    'jugador' => $posicion,
                    'error' => 'No se puede crear un jugador sin: ' .
                        implode(',', array_diff(Jugador::getArrayConCamposObligatorios(), array_keys($rawJugador)))
                ];
                continue;
            }

            if (!array_key_exists('dorsal', $rawJugador)) {
                $errores[] = [
                    'jugador' => $posicion,
                    'error' => 'No se puede asociar un jugador a una plantilla sin dorsal',
                ];
                continue;
            }

            if (array_key_exists('id', $rawJugador)) {
                $jugador = $entityManager->getRepository(Jugador::class)->find($rawJugador['id']);
                if (!$jugador) {
                    $errores[] = [
                        'jugador' => $posicion,
                        'error' => 'No existe ningún jugador con el id ' . $rawJugador['id'],
                    ];
                    continue;
                }
            } else {
                try {
                    $jugador = $serializer->deserialize(json_encode($rawJugador), Jugador::class, 'json');
                    $entityManager->persist($jugador);
                } catch (NotNormalizableValueException $exception) {
                    $errores[] = [
                        'jugador' => $posicion,
                        'error' => $exception->getMessage(),
                    ];
                    continue;
                }
            }

            $plantilla->agregarJugador($jugador, $rawJugador['dorsal']);
        }

        if ($plantilla->getJugadores()->isEmpty()) {
            return $this->json([
                'msg' => 'No se ha creado la plantilla. No se ha podido asociar ningún jugador correctamente',
                'errores' => $errores,
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($plantilla);
        $entityManager->flush();

        $response = [
            'msg' => 'Plantilla creada correctamente',
            'plantilla' => $plantilla->getId(),
        ];

        if (!empty($errores)) {
            $response['msg'] .= ' (con errores)';
            $response['errores'] = $errores;
        }

        return $this->json($response);
    }

    #[Route('/todas', name: 'listar', methods: ['GET'])]
    public function listar(): JsonResponse
    {
        $plantillas = $this->plantillaRepository->findAll();

        if (count($plantillas) == 0) {
            return $this->json([
                'msg' => 'No hay ningna plantilla creada',
            ]);
        }

        return $this->json(
            array_map(function (Plantilla $plantilla) {
                return [
                    'id_plantilla' => $plantilla->getId(),
                    'jugadores' => $plantilla->getJugadores()->count(),
                ];
            }, $plantillas)
        );
    }

    #[Route("/{idPlantilla}", name: "detalles", methods: ['GET'], requirements: ['idPlantilla' => Requirement::DIGITS])]
    public function detalles(int $idPlantilla, NormalizerInterface $normalizer): JsonResponse
    {
        $plantilla = $this->plantillaRepository->find($idPlantilla);
        if (!$plantilla) {
            return $this->json([
                'msg' => 'No existe ninguna plantilla con el id ' . $idPlantilla,
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'id_plantilla' => $idPlantilla,
            'numero_jugadores' => $plantilla->getJugadores()->count(),
            'jugadores' => $plantilla->getJugadores()->map(
                function (PlantillaJugador $plantillaJugador) use ($normalizer) {
                    return array_merge(
                        $normalizer->normalize($plantillaJugador->getJugador(), null, ['groups' => 'lista']),
                        ['dorsal' => $plantillaJugador->getDorsal()]
                    );
                }),
        ]);
    }

    #[Route('/{idPlantilla}', name: 'eliminar', requirements: ['idPlantilla' => Requirement::DIGITS], methods: ['DELETE'])]
    public function eliminar(int $idPlantilla, EquipoCompeticionRepository $equipoCompeticionRepository): JsonResponse
    {
        $plantilla = $this->plantillaRepository->find($idPlantilla);

        if (!$plantilla) {
            return $this->json([
                'Error: no existe ninguna plantilla con el id ' . $idPlantilla,
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!empty($equipoCompeticionRepository->getCompeticionesDePlantilla($plantilla))) {
            return $this->json([
                'Error: la plantilla no puede eliminarse. Está asociada a competiciones y equipos',
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->plantillaRepository->remove($plantilla);

        return $this->json([
            'msg' => 'La plantilla ha sido eliminada correctamente',
        ]);
    }

}
