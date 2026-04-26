<?php

namespace App\Controller;

use App\Entity\Arbitro;
use App\Entity\Competicion;
use App\Exception\APIException;
use App\Repository\ArbitroRepository;
use App\Util\JsonParserRequest;
use App\Util\ParamsCheckerTrait;
use App\Util\ResponseBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/arbitros', name: 'api_arbitro_')]
#[OA\Tag(name: 'Arbitros')]
final class ApiArbitroController extends AbstractController
{
    use ParamsCheckerTrait;
    use JsonParserRequest;

    public function __construct(
        private readonly ArbitroRepository   $arbitroRepository,
        private readonly SerializerInterface $serializer,
        private readonly TranslatorInterface $translator,
        private readonly ResponseBuilder     $responseBuilder)
    {
    }

    /**
     * Muestra los detalles de un árbitro
     */
    #[Route('/{id}', name: 'view', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['GET'])]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(description: 'ID del arbitro', type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: 'Petición procesada con éxito',
        content: new OA\JsonContent(ref: new Model(type: Arbitro::class, groups: ['view']))
    )]
    #[OA\Response(
        response: 404,
        description: 'Entidad no encontrada',
        content: new OA\JsonContent(ref: '#/components/schemas/404')
    )]
    #[OA\Response(
        response: 500,
        description: 'Error al procesar la petición',
        content: new OA\JsonContent(ref: '#/components/schemas/Error')
    )]
    public function indexAction(int $id, NormalizerInterface $normalizer): Response
    {
        $arbitro = $this->arbitroRepository->find($id);

        if (!$arbitro) {
            return $this->responseBuilder->createNotFoundResponse(
                $this->translator->trans('arbitro.not_found', ['%id%' => $id], 'messages')
            );
        }

        try {
            return $this->json($normalizer->normalize($arbitro, 'json', ['groups' => ['view']]));

        } catch (ExceptionInterface $ex) {
            return $this->responseBuilder->createExceptionResponse($ex);
        }
    }

    /**
     * Obtiene una lista con todos los árbitros disponibles
     */
    #[Route(methods: ['GET'])]
    #[OA\Parameter(
        name: 'pais',
        description: 'Código ISO3166-alpha 2 (https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'es'),
    )]
    #[OA\Parameter(
        name: 'competicion',
        description: 'ID de una competición',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Listado de árbitros según con filtros requeridos',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Arbitro::class, groups: ['list'])),
        )
    )]
    public function listAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        try {
            if ($request->query->has('pais') && !$request->query->has('competicion')) {
                $arbitros = $this->arbitroRepository->findBy(['country' => $this->getCountryName($request->query->get('pais'))]);

            } else if ($request->query->has('competicion')) {
                $competitionId = $request->query->get('competicion');
                $competicion = $entityManager->getRepository(Competicion::class)->find($competitionId);

                if (!$competicion) {
                    return $this->responseBuilder->createErrorResponseWithMessage($this->translator->trans('competition.not_found', ['%id%' => $competitionId], 'messages'));
                }

                $arbitros = $this->arbitroRepository->findByCompetition($competicion);

                if ($request->query->has('pais')) {
                    $countryName = $this->getCountryName($request->query->get('pais'));
                    $arbitros = array_filter($arbitros, static function (Arbitro $arbitro) use ($countryName) {
                        return $arbitro->getCountry() === $countryName;
                    });
                }

            } else {
                $arbitros = $this->arbitroRepository->findAll();
            }

            return $this->json([
                'status' => Response::HTTP_OK,
                'arbitros' => array_map(function (Arbitro $arbitro) {
                    return $this->serializer->normalize($arbitro, 'json', ['groups' => ['list']]);
                }, $arbitros),
            ]);

        } catch (APIException $ex) {
            return $this->responseBuilder->createErrorResponseWithMessage($ex->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @throws APIException
     */
    private function getCountryName(string $countryCode): string
    {
        $countryCode = mb_strtoupper($countryCode);

        if (!Countries::exists($countryCode)) {
            throw new APIException($this->translator->trans('generic.country_error', [], 'messages'));
        }

        return Countries::getName($countryCode, 'es');
    }

    /**
     * Crea un nuevo árbitro
     */
    #[Route(name: 'create', methods: ['POST'])]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Petición procesada con éxito',
        content: new OA\JsonContent(ref: '#/components/schemas/Created')
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'No se puede realizar la petición',
        content: new OA\JsonContent(ref: '#/components/schemas/400')
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/Error')
    )]
    #[OA\RequestBody(content: new Model(type: Arbitro::class, groups: ['create']))]
    public function createAction(Request $request): Response
    {
        try {
            if (!$this->checkIfRequestHasMandatoryParams(['name', 'country'], $request)) {
                return $this->responseBuilder->createMissingMandatoryParamsResponse($this->getMissingMandatoryParams());
            }

            $this->parseJsonRequest($request);

            if ($this->arbitroRepository->findOneBy(['name' => $this->jsonContent['name']])) {
                return $this->responseBuilder->createErrorResponseWithMessage(
                    $this->translator->trans('arbitro.already_exists', [], 'messages'),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $arbitro = $this->serializer->deserialize($request->getContent(), Arbitro::class, 'json', [
                DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
            ]);

            $this->arbitroRepository->save($arbitro);

            return $this->json(['status' => Response::HTTP_CREATED, 'arbitro' => $arbitro->getId()]);


        } catch (\JsonException $ex) {
            return $this->responseBuilder->createExceptionResponse($ex);

        } catch (PartialDenormalizationException $e) {
            return $this->responseBuilder->createPartialDenormalizationExceptionResponse($e);
        }
    }

    /**
     * Modifica un árbitro
     */
    #[Route('/{id}', name: 'update', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['PATCH'])]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(description: 'ID del arbitro', type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: 'Actualización completada correctamente',
        content: new OA\JsonContent(ref: '#/components/schemas/OK'),
    )]
    #[OA\Response(
        response: 404,
        description: 'Entidad no encontrada',
        content: new OA\JsonContent(ref: '#/components/schemas/404')
    )]
    #[OA\Response(
        response: 400,
        description: 'Petición procesada con errores',
        content: new OA\JsonContent(ref: '#/components/schemas/Error')
    )]
    #[OA\RequestBody(content: new Model(type: Arbitro::class, groups: ['create']))]
    public function updateAction(int $id, Request $request): Response
    {
        $arbitro = $this->arbitroRepository->find($id);
        if (!$arbitro) {
            return $this->responseBuilder->createNotFoundResponse(
                $this->translator->trans('arbitro.not_found', ['%id%' => $id], 'messages')
            );
        }

        try {
            $this->serializer->deserialize($request->getContent(), Arbitro::class, 'json', [
                DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
                AbstractNormalizer::OBJECT_TO_POPULATE => $arbitro,
            ]);

            if ($this->arbitroRepository->findOneBy(['name' => $arbitro->getName()])) {
                return $this->responseBuilder->createErrorResponseWithMessage(
                    $this->translator->trans('arbitro.already_exists', [], 'messages'),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $this->arbitroRepository->save($arbitro);

            return $this->json(['status' => 200]);

        } catch (PartialDenormalizationException $e) {
            return $this->responseBuilder->createPartialDenormalizationExceptionResponse($e);
        }
    }

    /**
     * Elimina un árbitro
     */
    #[Route('/{id}', name: 'delete', requirements: ['id' => Requirement::POSITIVE_INT], methods: ['DELETE'])]
    #[OA\Parameter(name: 'id', in: 'path', schema: new OA\Schema(description: 'ID del arbitro', type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: 'Arbitro eliminado correctamente',
        content: new OA\JsonContent(ref: '#/components/schemas/OK')
    )]
    #[OA\Response(
        response: 404,
        description: 'Entidad no encontrada',
        content: new OA\JsonContent(ref: '#/components/schemas/404')
    )]
    public function deleteAction(int $id): Response
    {
        $arbitro = $this->arbitroRepository->find($id);
        if (!$arbitro) {
            return $this->responseBuilder->createNotFoundResponse('Árbitro no encontrado');
        }

        $this->arbitroRepository->remove($arbitro);

        return $this->json(['status' => 200]);
    }
}
