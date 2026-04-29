<?php

namespace App\Controller;

use App\Entity\Arbitro;
use App\Entity\Equipo;
use App\Entity\Estadio;
use App\Entity\Partido;
use App\Exception\APIMissingMandatoryParamsException;
use App\Policy\ApiPolicy;
use App\Policy\MandatoryParamsPolicy;
use App\Repository\PartidoRepository;
use App\Util\JsonParserRequest;
use App\Util\ParamsCheckerTrait;
use App\Util\ResponseBuilder;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/partidos', name: 'api_partido_')]
#[OA\Tag(name: 'Partidos')]
final class ApiPartidoController extends AbstractController
{
    use JsonParserRequest;

    public function __construct(
        private readonly PartidoRepository      $partidoRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ResponseBuilder        $responseBuilder,
        private readonly TranslatorInterface    $translator,
        private readonly MandatoryParamsPolicy  $mandatoryParamsPolicy)
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
                new OA\Property('status', type: 'integer', example: 201),
                new OA\Property('partido', description: 'ID del partido', type: 'integer', example: 1),
                new OA\Property('errores', type: 'array', items: new OA\Items(schema: 'string')),
            ]
        ))
    ]
    #[OA\Response(
        response: 400,
        description: 'Petición mal formada',
        content: new OA\JsonContent(ref: '#/components/schemas/Error')
    )]
    #[OA\Response(
        response: 404,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/404')
    )]
    public function create(Request $request): Response
    {
        try {
            $this->mandatoryParamsPolicy->apply($request, [
                ApiPolicy::MANDATORY_PARAMS => ['equipoLocal', 'equipoVisitante', 'fecha'],
            ]);

            $this->parseJsonRequest($request);

            $equipoLocal = $this->entityManager->getRepository(Equipo::class)->find($this->jsonContent['equipoLocal']);

            if (!$equipoLocal) {
                return $this->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => $this->translator->trans('partido.error.local', [], 'messages'),
                ]);
            }

            $equipoVisitante = $this->entityManager->getRepository(Equipo::class)->find($this->jsonContent['equipoVisitante']);
            if (!$equipoVisitante) {
                return $this->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => $this->translator->trans('partido.error.visitante', [], 'messages'),
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
                    $errors[] = $this->translator->trans('arbitro.generic_not_found', [], 'messages');
                }
                $partido->setArbitro($arbitro);
            }

            if (isset($this->jsonContent['fecha'])) {
                $datetime = \DateTimeImmutable::createFromFormat(DATE_ATOM, $this->jsonContent['fecha']);
                if (!$datetime) {
                    $errors[] = $this->translator->trans('generic.date_format', ['%format%' => DATE_ATOM], 'messages');
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
                    $errors[] = $this->translator->trans('estadio.generic_not_found', [], 'messages');
                }
            }

            $this->partidoRepository->save($partido);

            $response = [
                'status' => Response::HTTP_CREATED,
                'partido' => $partido->getId(),
            ];

            if (!empty($errors)) {
                $response['message'] = 'Partido creado correctamente, pero con errores';
                $response['errors'] = $errors;
            }
            return $this->json($response, Response::HTTP_CREATED);

        } catch (APIMissingMandatoryParamsException|\JsonException $exception) {
            return $this->responseBuilder->createExceptionResponse($exception, Response::HTTP_BAD_REQUEST);
        }
    }
}
