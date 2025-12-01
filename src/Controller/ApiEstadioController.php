<?php

namespace App\Controller;

use App\ApiCursor\ApiCursor;
use App\ApiCursor\ApiCursorBuilder;
use App\Entity\Estadio;
use App\Exception\APIException;
use App\Repository\EstadioRepository;
use App\Util\JsonParserRequest;
use App\Util\ParamsCheckerTrait;
use App\Util\ResponseBuilder;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/estadios', name: 'api_estadio_')]
#[OA\Tag(name: 'Estadios')]
class ApiEstadioController extends AbstractController
{
    use ParamsCheckerTrait;
    use JsonParserRequest;
    use ResponseBuilder;

    public function __construct(private readonly EstadioRepository $estadioRepository,
                                private readonly ApiCursorBuilder  $cursorBuilder)
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

            $lastStadium = $stadiums[count($stadiums) - 1];
            $cursor->setLastID($lastStadium->getId());

            $normalizer = new ObjectNormalizer(new ClassMetadataFactory(new AttributeLoader()));
            $response = [
                'estadios' => array_map(static function (Estadio $estadio) use ($normalizer) {
                    return $normalizer->normalize($estadio, null, ['groups' => 'lista']);
                }, $stadiums),
            ];

            if (($nextPage = $cursor->getNextPage()) !== ApiCursor::LAST_PAGE) {
                $response[ApiCursorBuilder::CURSOR_PARAMETER_NAME] = $nextPage;
            }

            return $this->json($response);

        } catch (APIException $e) {
            return $this->buildExceptionResponse($e, $e->getCode());
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
                return $this->json([
                    'msg' => 'No existe ningún estadio con el id ' . $idEstadio,
                ], 264);
            }

            return $this->json($normalizer->normalize($estadio));

        } catch (ExceptionInterface $ex) {
            return $this->json([
                'msg' => 'Se ha producido un error al ejecutar la petición',
                'error' => $ex,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'msg', type: 'string'),
                new OA\Property(property: 'id', description: 'ID del nuevo estadio creado', type: 'integer'),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'No se puede realizar la petición',
        content: new OA\JsonContent(ref: '#/components/schemas/400'),
    )]
    #[OA\Response(
        response: 502,
        description: 'Error. Ya existe un estadio con el mismo nombre',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    public function nuevo(Request $request, SerializerInterface $serializer): Response
    {
        $camposObligatorios = ['nombre', 'ciudad', 'capacidad'];
        if (!$this->peticionConParametrosObligatorios($camposObligatorios, $request)) {
            return $this->buildResponseWithMissingMandatoryParams();
        }

        $this->parseJsonRequest($request);

        $estadio = $this->estadioRepository->findOneBy([
            'nombre' => $this->jsonContent['nombre'],
        ]);

        if ($estadio) {
            return $this->json([
                'msg' => sprintf('Ya existe un estadio con el nombre \'%s\'', $this->jsonContent['nombre']),
            ], 502);
        }

        /** @var Estadio $estadio */
        $estadio = $serializer->deserialize($request->getContent(), Estadio::class, 'json');
        $this->estadioRepository->save($estadio, true);

        return $this->json([
            'msg' => 'Estadio creado correctamente',
            'id' => $estadio->getId(),
        ]);
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
        response: 264,
        description: 'Estadio no encontrado',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    #[Route('/{idEstadio}', methods: ['PATCH'])]
    public function edita(int $idEstadio, Request $request, SerializerInterface $serializer): Response
    {
        $estadio = $this->estadioRepository->find($idEstadio);

        if (!$estadio) {
            return $this->json([
                'msg' => 'No existe ningún estadio con el id ' . $idEstadio,
            ], 264);
        }

        $serializer->deserialize($request->getContent(), Estadio::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $estadio]);
        $this->estadioRepository->save($estadio, true);

        return $this->json([
            'msg' => 'Estadio actualizado correctamente',
        ]);
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
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    #[OA\Response(
        response: 264,
        description: 'Estadio no encontrado',
        content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')
    )]
    #[Route('/{idEstadio}', methods: ['DELETE'])]
    public function elimina(int $idEstadio): Response
    {
        $estadio = $this->estadioRepository->find($idEstadio);

        if (!$estadio) {
            return $this->json([
                'msg' => 'No existe ningún estadio con el id ' . $idEstadio,
            ], 264);
        }

        $this->estadioRepository->remove($estadio, true);

        return $this->json([
            'msg' => 'Estadio eliminado correctamente',
        ]);
    }
}
