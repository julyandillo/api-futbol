<?php

namespace App\Controller;

use App\Entity\Arbitro;
use App\Entity\Equipo;
use App\Entity\Estadio;
use App\Entity\Partido;
use App\Repository\PartidoRepository;
use App\Util\JsonParserRequest;
use App\Util\ParamsCheckerTrait;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/partidos', name: 'api_partido_')]
#[OA\Tag(name: 'Partidos')]
final class ApiPartidoController extends AbstractController
{
    use ParamsCheckerTrait;
    use JsonParserRequest;

    public function __construct(
        private readonly PartidoRepository      $partidoRepository,
        private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Crea un nuevo partido
     */
    #[Route(name: 'create', methods: ['POST'])]
    #[OA\RequestBody(
        content: new OA\JsonContent(properties: [
            new OA\Property('equipoLocal', type: 'integer', example: 1),
            new OA\Property('equipoVisitante', type: 'integer', example: 2),
            new OA\Property('arbitro', type: 'integer', example: 12),
            new OA\Property('estadio', type: 'integer', example: 3),
            new OA\Property('fecha', description: 'Formato: ' . DATE_ATOM, type: 'string', example: '2025-01-23T21:00:00P'),
        ], type: 'object')
    )]
    #[OA\Response(
        response: 201,
        description: 'Partido creado correctamente',
        content: new OA\JsonContent(
            properties: [
                new OA\Property('code', type: 'integer', example: 201),
                new OA\Property('partido', description: 'ID del partido', type: 'integer', example: 1),
                new OA\Property('errores', type: 'array', items: new OA\Items(schema: 'string')),
            ]
        ))
    ]
    #[OA\Response(
        response: 400,
        description: 'Petición mal formada',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    #[OA\Response(
        response: 264,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    public function create(Request $request): Response
    {
        try {
            if (!$this->peticionConParametrosObligatorios(['equipoLocal', 'equipoVisitante', 'fecha',], $request)) {
                return $this->buildResponseWithMissingMandatoryParams();
            }

            $equipoLocal = $this->entityManager->getRepository(Equipo::class)->find($this->jsonContent['equipoLocal']);
            $equipoVisitante = $this->entityManager->getRepository(Equipo::class)->find($this->jsonContent['equipoVisitante']);

            if (!$equipoLocal) {
                return $this->json([
                    'code' => 500,
                    'msg' => 'Equipo local no encontrado',
                ]);
            }

            if (!$equipoVisitante) {
                return $this->json([
                    'code' => 500,
                    'msg' => 'Equipo visitante no encontrado',
                ]);
            }

            $partido = new Partido();
            $partido
                ->setEquipoLocal($equipoLocal)
                ->setEquipoVisitante($equipoVisitante);

            $errors = [];
            if (isset($this->jsonContent['arbitro'])) {
                $arbitro = $this->entityManager->getRepository(Arbitro::class)->find($this->jsonContent['arbitro']);
                if (!$arbitro) {
                    $errors[] = 'Arbitro no encontrado';
                }
                $partido->setArbitro($arbitro);
            }

            if (isset($this->jsonContent['fecha'])) {
                $datetime = \DateTimeImmutable::createFromFormat(DATE_ATOM, $this->jsonContent['fecha']);
                if (!$datetime) {
                    $errors[] = 'No se ha podido guardar la fecha del partido, el formato debe ser ' . DATE_ATOM;
                }
                $partido->setDatetime($datetime);
            }

            if (isset($this->jsonContent['estadio'])) {
                if (is_int($this->jsonContent['estadio'])
                    && ($estadio = $this->entityManager->getRepository(Estadio::class)->find($this->jsonContent['estadio']))
                ) {
                    $partido->setEstadio($estadio);
                } else if ($this->jsonContent['estadio'] === 'local') {
                    $partido->setEstadio($equipoLocal->getEstadio());
                } else {
                    $errors[] = 'Estadio no encontrado';
                }
            }

            $this->partidoRepository->save($partido);

            $response = [
                'code' => Response::HTTP_CREATED,
                'partido' => $partido->getId(),
            ];

            if (!empty($errors)) {
                $response['msg'] = 'Partido creado correctamente, pero con errores';
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
}
