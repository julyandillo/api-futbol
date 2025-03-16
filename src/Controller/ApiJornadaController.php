<?php

namespace App\Controller;

use App\Entity\Competicion;
use App\Entity\Jornada;
use App\Entity\JornadaPartido;
use App\Entity\Partido;
use App\Repository\CompeticionRepository;
use App\Repository\JornadaRepository;
use App\Util\JsonParserRequest;
use App\Util\ParamsCheckerTrait;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/jornadas', name: 'api_jornada_')]
#[OA\Tag(name: 'Jornadas')]
final class ApiJornadaController extends AbstractController
{
    use ParamsCheckerTrait;
    use JsonParserRequest;

    public function __construct(
        private readonly JornadaRepository      $jornadaRepository,
        private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Muestra los detalles de una jornada a través de su ID)
     */
    #[Route('/{id}', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: 'Detalles de la jornada',
        content: new OA\JsonContent(ref: new Model(type: Jornada::class)),
    )]
    #[OA\Response(
        response: 264,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    public function index(int $id, NormalizerInterface $normalizer): Response
    {
        $jornada = $this->jornadaRepository->find($id);
        if (!$jornada) {
            return $this->json([
                'code' => 264,
                'msg' => 'Jornada no encontrada'
            ], 264);
        }

        return $this->json($normalizer->normalize($jornada, 'json', [
            AbstractNormalizer::CALLBACKS => [
                'competicion' => fn(object $attributeValue) => $attributeValue->getId(),
            ]
        ]));
    }

    /**
     * Muestra el detalle de una jornada (usando el número de jornada) de una determinada competición
     */
    #[Route('/{roundNumber}/competicion/{competitionId}', name: '_view', methods: ['GET'])]
    #[OA\Parameter(name: 'roundNumber', description: 'número de jornada', in: 'path', schema: new OA\Schema('integer'))]
    #[OA\Parameter(name: 'competitionId', description: 'ID de la competición', in: 'path', schema: new OA\Schema('integer'))]
    #[OA\Response(
        response: 200,
        description: 'Detalles de la jornada',
        content: new OA\JsonContent(properties: [
            new OA\Property('code', type: 'integer', example: 200),
            new OA\Property('jornada', ref: new Model(type: Jornada::class, groups: ['list', 'detail']))
        ], type: 'object')
    )]
    #[OA\Response(
        response: 264,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    public function view(
        int                   $roundNumber,
        int                   $competitionId,
        CompeticionRepository $competicionRepository,
        NormalizerInterface   $normalizer
    ): Response
    {
        $competicion = $competicionRepository->find($competitionId);
        if (!$competicion) {
            return $this->json([
                'code' => 264,
                'msg' => 'No existe la competición'
            ], 264);
        }

        /** @var Jornada $jornada */
        foreach ($competicion->getJornadas() as $jornada) {
            if ($jornada->getNumber() === $roundNumber) {
                return $this->json(['jornada' => $normalizer->normalize($jornada, 'json', ['groups' => ['list', 'detail']])]);
            }
        }

        return $this->json([
            'code' => 264,
            'msg' => 'La competición no tiene ninguna jornada con el ID deseado',
        ], 264);
    }

    /**
     * Muestra un listado de todas las jornadas de una determinada competición
     */
    #[Route('/competicion/{competitionID}', name: 'list', requirements: ['competitionID' => Requirement::DIGITS], methods: ['GET'])]
    #[OA\Parameter(name: 'competitionID', in: 'path', schema: new OA\Schema('integer'))]
    #[OA\Response(
        response: 200,
        description: 'OK',
        content: new OA\JsonContent(properties: [
            new OA\Property('code', type: 'integer', example: 200),
            new OA\Property(
                'jornadas',
                type: 'array',
                items: new OA\Items(ref: new Model(type: Jornada::class, groups: ['list']))
            ),
        ], type: 'object')
    )]
    #[OA\Response(
        response: 264,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    #[OA\Parameter(
        name: 'competitionID',
        description: 'ID de la competición',
        in: 'path',
        schema: new OA\Schema(type: 'integer')
    )]
    public function list(int $competitionID, CompeticionRepository $competicionRepository, NormalizerInterface $normalizer): Response
    {
        $competicion = $competicionRepository->find($competitionID);
        if (!$competicion) {
            return $this->json([
                'code' => 264,
                'msg' => 'No existe ninguna competición con el id ' . $competitionID
            ], 264);
        }

        return $this->json([
            'code' => 200,
            'jornadas' => array_map(static function (Jornada $jornada) use ($normalizer) {
                return $normalizer->normalize($jornada, 'json', ['groups' => 'list']);
            }, $competicion->getJornadas()->toArray()),
        ]);
    }

    /**
     * Crea una nueva jornada y se asocia a una competición existente.
     * Opcionalmente, también se pueden asociar los partidos en la misma petición
     */
    #[Route(name: 'create', methods: ['POST'])]
    #[OA\Response(
        response: 200,
        description: 'Petición procesada correctamente',
        content: new OA\JsonContent(
            properties: [
                new OA\Property('code', type: 'integer', example: 200),
                new OA\Property('jornada', description: 'ID de la jornada creada', type: 'integer', example: 1),
            ],
            type: 'object'),
    )]
    #[OA\Response(
        response: 264,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    public function create(Request $request, SerializerInterface $serializer): Response
    {
        try {
            if (!$this->peticionConParametrosObligatorios(['number', 'competicion'], $request)) {
                return $this->buildResponseWithMissingMandatoryParams();
            }

            $this->parseJsonRequest($request);

            $competicion = $this->entityManager->getRepository(Competicion::class)->find($this->jsonContent['competicion']);
            if (!$competicion) {
                return $this->json([
                    'code' => 264,
                    'msg' => 'No existe ninguna competición con el ID ' . $this->jsonContent['competicion'],
                ], 264);
            }

            if ($this->jornadaRepository->findOneBy(['number' => $this->jsonContent['number'], 'competicion' => $competicion])) {
                return $this->json([
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'msg' => 'Jornada ya existente para la competición',
                ]);
            }

            $jornada = $serializer->deserialize($request->getContent(), Jornada::class, 'json');
            $jornada->setCompeticion($competicion);

            $this->entityManager->persist($jornada);

            if (isset($this->jsonContent['partidos'])) {
                if (!is_array($this->jsonContent['partidos'])) {
                    return $this->json([
                        'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                        'msg' => 'Formato de partidos incorrecto, debe ser un array',
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                $errors = $this->addMatchesToJornada($jornada, $this->jsonContent['partidos']);
            }

            $this->entityManager->flush();

            $response = [
                'code' => Response::HTTP_CREATED,
                'jornada' => $jornada->getId(),
            ];

            if (!empty($errors)) {
                $response['errors'] = $errors;
            }

            return $this->json($response, Response::HTTP_CREATED);

        } catch (\JsonException $exception) {
            return $this->json([
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'msg' => $exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualiza los detalles de una jornada
     */
    #[Route('/{idJornada}', name: 'update', requirements: ['idJornada' => Requirement::POSITIVE_INT], methods: ['PATCH'])]
    #[OA\Parameter(name: 'idJornada', in: 'path', schema: new OA\Schema('integer'))]
    #[OA\Response(
        response: 200,
        description: 'Petición procesada correctamente',
        content: new OA\JsonContent(
            properties: [new OA\Property('code', type: 'integer', example: 200)],
            type: 'object'),
    )]
    #[OA\Response(
        response: 264,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    public function update(int $idJornada, Request $request, SerializerInterface $serializer): Response
    {
        $jornada = $this->jornadaRepository->find($idJornada);

        if (!$jornada) {
            return $this->json([
                'code' => 264,
                'msg' => 'No existe ninguna jornada con el id ' . $idJornada,
            ]);
        }

        $serializer->deserialize($request->getContent(), Jornada::class, 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $jornada,
        ]);

        $this->jornadaRepository->save($jornada);

        return $this->json(['code' => 200]);
    }

    private function addMatchesToJornada(Jornada $jornada, array $idPartidos): array
    {
        $errors = [];
        foreach ($idPartidos as $partidoID) {
            if (!is_int($partidoID)) {
                $errors[] = [
                    'partido' => $partidoID,
                    'error' => 'El id del partido no es un entero',
                ];
                continue;
            }

            $partido = $this->entityManager->getRepository(Partido::class)->find($partidoID);
            if (!$partido) {
                $errors[] = [
                    'partido' => $partidoID,
                    'error' => 'No existe el partido, creálo antes de asociarlo a la jornada',
                ];
                continue;
            }

            if ($this->entityManager->getRepository(JornadaPartido::class)->findOneBy(['partido' => $partido,])) {
                $errors[] = [
                    'partido' => $partidoID,
                    'error' => 'El partido ya existe en una jornada diferente',
                ];
                continue;
            }

            if ($this->entityManager->getRepository(JornadaPartido::class)->findOneBy(['partido' => $partido, 'jornada' => $jornada])) {
                continue;
            }

            $jornadaPartido = new JornadaPartido();
            $jornadaPartido
                ->setJornada($jornada)
                ->setPartido($partido);

            $this->entityManager->persist($jornadaPartido);
        }

        return $errors;
    }

    /**
     * Agrega partidos a una jornada existente
     */
    #[Route('/{idJornada}/matches', name: 'matches', requirements: ['idJornada' => Requirement::DIGITS], methods: ['POST'])]
    #[OA\Response(
        response: 200,
        description: 'Petición procesada correctamente',
        content: new OA\JsonContent(
            properties: [new OA\Property('code', type: 'integer', example: 200)],
            type: 'object'),
    )]
    #[OA\Response(
        response: 264,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    public function matches(int $idJornada, Request $request): Response
    {
        try {
            if (!$this->peticionConParametrosObligatorios(['partidos'], $request)) {
                return $this->buildResponseWithMissingMandatoryParams();
            }

            $this->parseJsonRequest($request);

            $jornada = $this->jornadaRepository->find($idJornada);
            if (!$jornada) {
                return $this->json([
                    'code' => 264,
                    'msg' => 'No existe ninguna jornada con el id ' . $idJornada,
                ], 264);
            }

            $errors = $this->addMatchesToJornada($jornada, $this->jsonContent['partidos']);

            if (empty($errors)) {
                return $this->json(['code' => 200]);
            }

            return $this->json([
                'code' => Response::HTTP_CREATED,
                'msg' => 'Petición procesada correctamente, pero con errores',
                'errors' => $errors,
            ], Response::HTTP_CREATED);

        } catch (\JsonException $exception) {
            return $this->json([
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'msg' => $exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
