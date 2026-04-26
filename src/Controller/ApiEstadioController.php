<?php

namespace App\Controller;

use App\ApiCursor\ApiCursorBuilder;
use App\Entity\Estadio;
use App\Exception\APIException;
use App\Repository\EstadioRepository;
use App\Util\JsonParserRequest;
use App\Util\PagesCursorTrait;
use App\Util\ParamsCheckerTrait;
use App\Util\ResponseBuilder;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/estadios', name: 'api_estadio_')]
#[OA\Tag(name: 'Estadios')]
class ApiEstadioController extends AbstractController
{
    use ParamsCheckerTrait;
    use JsonParserRequest;
    use PagesCursorTrait;

    public function __construct(private readonly EstadioRepository   $estadioRepository,
                                private readonly ApiCursorBuilder    $cursorBuilder,
                                private readonly ResponseBuilder     $responseBuilder,
                                private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Obtiene una lista con todos los estadios disponibles
     *
     * Devuelve un cursor para obtener los siguientes resultados. Si en la petición aparece un cursor, los demás
     * parámetros serán ignorados y sólo se tendrá en cuenta el cursor
     */
    #[Route(name: 'listar', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Array con los estadios coincidentes con los filtros y en el orden requerido. Por defecto ordenados alfabéticamente por nombre',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Estadio::class, groups: ['lista']))
        )
    )]
    public function listAction(Request $request): Response
    {
        try {
            $this->cursorBuilder->setAllowFieldOrders(['nombre', 'capacidad', 'construccion']);
            $this->cursorBuilder->setAllowFieldFilters([
                'capacidad',
                'capacidad_min',
                'capacidad_max',
                'construccion',
                'construccion_min',
                'construccion_max',
            ]);
            $cursor = $this->cursorBuilder->buildCursorWithRequest($request);

            $stadiums = $this->estadioRepository->findByCursor($cursor);

            if (empty($stadiums)) {
                return $this->json([
                    'code' => Response::HTTP_OK,
                    'estadios' => [],
                ]);
            }

            $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AttributeLoader()));
            $response = [
                'status' => Response::HTTP_OK,
                'estadios' => array_map(static function (Estadio $estadio) use ($normalizer) {
                    return $normalizer->normalize($estadio, null, ['groups' => 'lista']);
                }, $stadiums),
            ];

            $this->addNextPageFieldInResponse($response, $cursor);

            return $this->json($response);

        } catch (APIException $e) {
            return $this->responseBuilder->createExceptionResponse($e, $e->getCode());
        }
    }

    /**
     * Obtiene toda la información de un estadio
     */
    #[Route('/{idEstadio}', requirements: ['idEstadio' => Requirement::DIGITS], methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Información del estadio',
        content: new Model(type: Estadio::class)
    )]
    public function index(int $idEstadio, NormalizerInterface $normalizer): Response
    {
        try {
            $estadio = $this->estadioRepository->find($idEstadio);

            if (!$estadio) {
                return $this->responseBuilder->createNotFoundResponse(
                    $this->translator->trans('estadio.not_found', ['%id%' => $idEstadio], 'messages')
                );
            }

            return $this->json($normalizer->normalize($estadio));

        } catch (ExceptionInterface $ex) {
            return $this->responseBuilder->createExceptionResponse($ex);
        }
    }

    /**
     * Crea un nuevo estadio
     */
    #[Route(name: 'nuevo', methods: ['POST'])]
    #[OA\RequestBody(content: new Model(type: Estadio::class, groups: ['create']))]
    #[OA\Response(
        response: 200,
        description: 'Estadio creado correctamente',
        content: new OA\JsonContent(ref: '#/components/schemas/Created')
    )]
    #[OA\Response(
        response: 400,
        description: 'No se puede realizar la petición',
        content: new OA\JsonContent(ref: '#/components/schemas/400'),
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: 'No se puede procesar la petición, ha ocurrido un error de validación',
        content: new OA\JsonContent(ref: '#/components/schemas/422')
    )]
    public function createAction(Request $request, SerializerInterface $serializer): Response
    {
        try {
            $camposObligatorios = ['nombre', 'ciudad', 'capacidad'];
            if (!$this->checkIfRequestHasMandatoryParams($camposObligatorios, $request)) {
                return $this->responseBuilder->createMissingMandatoryParamsResponse($this->getMissingMandatoryParams());
            }

            $this->parseJsonRequest($request);

            $estadio = $this->estadioRepository->findOneBy([
                'nombre' => $this->jsonContent['nombre'],
            ]);

            if ($estadio) {
                return $this->responseBuilder->createErrorResponseWithMessage(
                    $this->translator->trans('estadio.already_exists', [], 'messages'),
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            /** @var Estadio $estadio */
            $estadio = $serializer->deserialize($request->getContent(), Estadio::class, 'json');
            $this->estadioRepository->save($estadio, true);

            return $this->json([
                'status' => Response::HTTP_OK,
                'estadio' => $estadio->getId(),
            ]);

        } catch (\JsonException $ex) {
            return $this->responseBuilder->createExceptionResponse($ex);

        } catch (PartialDenormalizationException $e) {
            return $this->responseBuilder->createPartialDenormalizationExceptionResponse($e);
        }
    }

    /**
     * Modifica la información sobre un estadio.
     *
     * @param int $idEstadio
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return Response
     */
    #[OA\RequestBody(content: new Model(type: Estadio::class, groups: ['update']))]
    #[OA\Response(
        response: 200,
        description: 'OK',
    )]
    #[OA\Response(
        response: 404,
        description: 'Estadio no encontrado',
        content: new OA\JsonContent(ref: '#/components/schemas/404')
    )]
    #[Route('/{idEstadio}', methods: ['PATCH'])]
    public function update(int $idEstadio, Request $request, SerializerInterface $serializer): Response
    {
        $estadio = $this->estadioRepository->find($idEstadio);

        if (!$estadio) {
            return $this->responseBuilder->createNotFoundResponse(
                $this->translator->trans('estadio.not_found', ['%id%' => $idEstadio], 'messages')
            );
        }

        try {
            $serializer->deserialize($request->getContent(), Estadio::class, 'json', [
                    AbstractNormalizer::OBJECT_TO_POPULATE => $estadio]
            );
            $this->estadioRepository->save($estadio, true);

            return $this->json([
                'status' => Response::HTTP_OK,
            ]);

        } catch (PartialDenormalizationException $e) {
            return $this->responseBuilder->createPartialDenormalizationExceptionResponse($e);
        }
    }

    /**
     * Elimina un estadio
     *
     * @param int $idEstadio
     * @return Response
     */
    #[OA\Response(
        response: 200,
        description: 'OK',
        content: new OA\JsonContent(ref: '#/components/schemas/OK')
    )]
    #[OA\Response(
        response: 404,
        description: 'Estadio no encontrado',
        content: new OA\JsonContent(ref: '#/components/schemas/404')
    )]
    #[Route('/{idEstadio}', methods: ['DELETE'])]
    public function delete(int $idEstadio): Response
    {
        $estadio = $this->estadioRepository->find($idEstadio);

        if (!$estadio) {
            return $this->responseBuilder->createNotFoundResponse(
                $this->translator->trans('estadio.not_found', ['%id%' => $idEstadio], 'messages')
            );
        }

        $this->estadioRepository->remove($estadio, true);

        return $this->json([
            'status' => Response::HTTP_OK,
        ]);
    }
}
